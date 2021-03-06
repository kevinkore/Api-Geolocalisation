<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Oka\PaginationBundle\Exception\PaginationException;
use Oka\PaginationBundle\Pagination\PaginationManager;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Oka\InputHandlerBundle\Annotation\AccessControl;
use Oka\InputHandlerBundle\Annotation\RequestContent;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Validator\Constraints as assert;

#[Route(path: "/users", name: "user_", requirements: ["id" => "^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$"], defaults: ["version" => "v1", "protocol" => "rest"])]
Class UserController extends AbstractController
{
    /**
     * list user.
    */
    #[Route(name:"list", methods:"GET")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function list(Request $request, PaginationManager $pm): Response
    {
        try {
            $page = $pm->paginate('user', $request, [], ['createdAt' => 'DESC']);
        } catch (PaginationException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $this->json(
            $page->toArray(),
            $page->getPageNumber() > 1 ? 206 : 200,
            [],
            ['groups' => $request->query->has('details') ? ['details'] : ['summary']]
        );
    }

    /**
     * Update password.
     *
     * @RequestContent(constraints="updatePasswordConstraints")
     */
    #[Route(path: '/{id}', name: 'update_password', methods: ["PUT", "PATCH"])]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function updatePassword(EntityManagerInterface $em, User $account, UserPasswordHasherInterface $userPasswordHasher, array $requestContent): Response
    {
        /** @var User $user */
        $user = $account->getUser();
        $oldPasswordIsValid = $userPasswordHasher->isPasswordValid(
            $user,
            $requestContent['oldPassword']
        );

        if (false === $oldPasswordIsValid) {
            throw new BadCredentialsException("password not valid");
        }

        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $requestContent['newPassword']
            )
        );
        $em->flush();

        return $this->json(null, 204 );
    }

    /**
     * activated user.
     *
     * @RequestContent(constraints="activationConstraints")
    */
    #[Route( name: "activation", methods: "POST")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function activation(EntityManagerInterface $em, array $requestContent)
    {
        $email = $requestContent['email'];
        $token = $requestContent['otpToken'];
        $user= $em->getRepository(User::class)->findOneBy(["email"=>$email]);
        $secret = Base32::encodeUpper("Mum");
        $otp = TOTP::create($secret,300,'sha1',8);
        if ($otp->verify($token))
        {
            $user->setIsActivated(true);
            $em->persist($user);
            $em->flush();
        }else{
            return $this->json("token expire or invalid",401);
        }
        return $this->json(null,201);
    }

    /**
     * Delete user.
     */
    #[Route(path: "/{id}", name: "delete", methods: "DELETE")]
    #[AccessControl(["version" => "v1", "protocol" => "rest", "formats" => "json"])]
    public function delete(EntityManagerInterface $em, User $user): Response
    {
        try {
            $em->remove($user);
            $em->flush();
        } catch (Exception $e) {
            throw new ConflictHttpException($this->contenair->get('translator')->trans('http_error.request_cannot_be_processed', ['%id%' => $user->getId()], 'errors'), $e);
        }

        return new JsonResponse(null, 204);
    }

    private static function updatePasswordConstraints(): Assert\Collection
    {
        return new Assert\Collection(
            [
                //'account' => new Assert\Required(new Assert\Uuid()),
                'oldPassword' => new Assert\Required(new Assert\NotBlank()),
                'newPassword' => new Assert\Required(new Assert\NotCompromisedPassword()),
                'email' => new Assert\Required(new Assert\NotBlank()),
                'otpToken' => new Assert\Required(new Assert\NotBlank()),
            ]
        );
    }
    private static function activationConstraints(): Assert\Collection
    {
        return new Assert\Collection(
            [
                //'account' => new Assert\Required(new Assert\Uuid()),
                'email' => new Assert\Required(new Assert\NotBlank()),
                'otpToken' => new Assert\Required(new Assert\NotBlank()),
            ]
        );
    }
}