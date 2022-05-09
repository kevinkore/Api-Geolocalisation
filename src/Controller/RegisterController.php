<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Oka\InputHandlerBundle\Annotation\AccessControl;
use Oka\InputHandlerBundle\Annotation\RequestContent;
use Symfony\Component\Validator\Constraints as assert;

#[Route(path: "/users", name: "user_", requirements: ["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
Class RegisterController extends AbstractController
{
    /**
     * create a user.
     * @RequestContent(constraints="createConstraints")
     */
    #[Route(name:"create", methods:"POST")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function create(EntityManagerInterface $em, array $requestContent): Response
    {
        $user = $this->edit(new User(), $requestContent);

        $em->persist($user);
        $em->flush();

        return $this->json($user, 201);
    }


    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function edit(object $object, array $requestContent): object
    {
        /** @var User $user */
        $user = parent::edit($object, $requestContent);
        $plainPassword = $requestContent['password'];
        if (true === isset($plainPassword)) {
            $em = $this->container->get("password.hashes");
            $hashedPassword = $em->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword)->setRoles(["ROLE_ADMIN"]);
        }

        return $user;
    }

    protected static function getExcludedFields(): array
    {
        return ["password"];
    }

    private static function createConstraints(): Assert\Collection
    {
        return self::itemConstraints(true);
    }

    private static function itemConstraints(bool $required): Assert\Collection
    {
        $className = true === $required ? Assert\Required::class : Assert\Optional::class;

        return new Assert\Collection([
            'email' => new $className(new Assert\NotBlank()),
            'password' => new $className(new Assert\NotBlank()),
        ]);
    }
}