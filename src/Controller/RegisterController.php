<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Oka\InputHandlerBundle\Annotation\AccessControl;
use Oka\InputHandlerBundle\Annotation\RequestContent;
use Symfony\Component\Validator\Constraints as assert;

#[Route(path: "/register", name: "user_", defaults: ["version" => "v1", "protocol" => "rest"])]
Class RegisterController extends AbstractController
{

    /**
     * create a user.
     *
     * @RequestContent(constraints="createConstraints")
     * @throws Exception
     */
    #[Route(name:"create", methods:"POST")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function create(EntityManagerInterface $em, array $requestContent): Response
    {
        $user = $this->edit(new user, $requestContent);
        $em->persist($user);
        $em->flush();

        $secret = Base32::encodeUpper("Mum");
        $otp = TOTP::create($secret,300,'sha1',8);
        $email = (new TemplatedEmail())
            ->from(new Address($this->getParameter("app.admin_email"),'Confirmation Email'))
            ->to($user->getEmail())
            ->htmlTemplate('EmailConfirmation.html.twig')->context(['token'=>$otp->now()]);
        try {
            /** * @var MailerInterface $mailer */
            $mailer = $this->container->get("mailer.interface");
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new Exception('Exception received : '.$e->getMessage());
        }

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
            /** @var UserPasswordHasherInterface $hashes */
            $hashes = $this->container->get("password.hashes");
            $hashedPassword = $hashes->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword)->setRoles(["ROLE_ADMIN"])->setIsActivated(false);
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

        return new Assert\Collection(
        [
            'email' => new $className(new Assert\Email()),
            'password' => new $className(new Assert\NotCompromisedPassword()),
        ]);
    }
}