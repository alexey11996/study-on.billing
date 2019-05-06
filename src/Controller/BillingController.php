<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerBuilder;
use App\DTO\BillingUserFormModel;
use Swagger\Annotations as SWG;
use App\Entity\BillingUser;

class BillingController extends AbstractController
{
    /**
    * @Route("api/v1/auth", name="login", methods={"POST"})
    * @SWG\Post(
    *     path="/api/v1/auth",
    *     summary="Authorize user",
    *     tags={"Authorization"},
    *     produces={"application/json"},
    *     consumes={"application/json"},
    *     @SWG\Parameter(
    *          name="body",
    *          in="body",
    *          required=true,
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="username",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="password",
    *                  type="string"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=200,
    *          description="Login successful",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="token",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="roles",
    *                  type="array",
    *                  @SWG\Items(type="string")
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=400,
    *          description="Bad request",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="message",
    *                  type="array",
    *                  @SWG\Items(type="string")
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=401,
    *          description="Bad credentionals",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="code",
    *                  type="integer"
    *              ),
    *              @SWG\Property(
    *                  property="message",
    *                  type="string"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=404,
    *          description="Page not found",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="code",
    *                  type="integer"
    *              ),
    *              @SWG\Property(
    *                  property="message",
    *                  type="string"
    *              )
    *          )
    *     )
    * )
    */
    public function login()
    {
    }

    /**
    * @Route("api/v1/register", name="register", methods={"POST"})
    * @SWG\Post(
    *     path="/api/v1/register",
    *     summary="Register user",
    *     tags={"Registration"},
    *     produces={"application/json"},
    *     consumes={"application/json"},
    *     @SWG\Parameter(
    *          name="body",
    *          in="body",
    *          required=true,
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="email",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="password",
    *                  type="string"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=201,
    *          description="Register successful",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="token",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="roles",
    *                  type="array",
    *                  @SWG\Items(type="string")
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=400,
    *          description="Bad request",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="errors",
    *                  type="array",
    *                  @SWG\Items(type="string")
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=500,
    *          description="Invalid JSON",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="code",
    *                  type="integer"
    *              ),
    *              @SWG\Property(
    *                  property="message",
    *                  type="string"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=404,
    *          description="Page not found",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="code",
    *                  type="integer"
    *              ),
    *              @SWG\Property(
    *                  property="message",
    *                  type="string"
    *              )
    *          )
    *     )
    * )
    */
    public function register(Request $request, ValidatorInterface $validator, JWTTokenManagerInterface $JWTManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $response = new Response();
        $serializer = SerializerBuilder::create()->build();
        $userDto = $serializer->deserialize($request->getContent(), BillingUserFormModel::class, 'json');
        $errors = $validator->validate($userDto);
        if (count($errors) > 0) {
            $jsonErrors = [];
            foreach ($errors as $error) {
                array_push($jsonErrors, $error->getMessage());
            }
            $response->setContent(json_encode(['errors' => $jsonErrors]));
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        } else {
            $user = BillingUser::fromDto($userDto, $passwordEncoder);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $response->setContent(json_encode(['token' => $JWTManager->create($user), 'roles' => $user->getRoles()]));
            $response->setStatusCode(Response::HTTP_CREATED);
        }
        return $response;
    }
}
