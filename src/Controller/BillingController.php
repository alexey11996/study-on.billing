<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\Security;
use JMS\Serializer\SerializerBuilder;
use App\DTO\BillingUserFormModel;
use App\DTO\CourseFormModel;
use Swagger\Annotations as SWG;
use App\Entity\BillingUser;
use App\Service\PaymentService;
use App\Entity\Course;
use App\Entity\Transaction;

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
    *              ),
    *              @SWG\Property(
    *                  property="refresh_token",
    *                  type="string"
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
    *              ),
    *              @SWG\Property(
    *                  property="refresh_token",
    *                  type="string"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=400,
    *          description="Bad request",
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
    public function register(Request $request, ValidatorInterface $validator, JWTTokenManagerInterface $JWTManager, UserPasswordEncoderInterface $passwordEncoder, RefreshTokenManagerInterface $refreshTokenManager, PaymentService $paymentService)
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
            $response->setContent(json_encode(['code' => 400, 'message' => $jsonErrors]));
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        } else {
            $user = BillingUser::fromDto($userDto, $passwordEncoder);

            $refreshToken = $refreshTokenManager->create();
            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setRefreshToken();
            $refreshToken->setValid((new \DateTime())->modify('+1 month'));

            $refreshTokenManager->save($refreshToken);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $paymentService->depositTransaction($user->getId());

            $response->setContent(json_encode(['token' => $JWTManager->create($user), 'roles' => $user->getRoles(), 'refresh_token' => $refreshToken->getRefreshToken()]));
            $response->setStatusCode(Response::HTTP_CREATED);
        }
        return $response;
    }

    /**
     * @Route("api/v1/users/current", name="current_user", methods={"GET"})
     * @SWG\Get(
     *    path="/api/v1/users/current",
     *    summary="Get current user info",
     *    tags={"Current User"},
     *    produces={"application/json"},
     *    @SWG\Response(
     *        response=200,
     *        description="Successful fetch user object",
     *        @SWG\Schema(
     *             @SWG\Property(
     *                 property="username",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="roles",
     *                 type="array",
     *                 @SWG\Items(type="string")
     *             ),
     *              @SWG\Property(
     *                 property="balance",
     *                 type="number"
     *             )
     *          )
     *      ),
     *    @SWG\Response(
     *        response="401",
     *        description="Unauthorized user",
     *    ),
     * )
     *    @Security(name="Bearer")
     */
    public function currentUser()
    {
        $user = $this->getUser();
        
        $response = new Response();
        $response->setContent(json_encode(["username" => $user->getUsername(), "roles" => $user->getRoles(), "balance" => $user->getBalance()]));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    /**
    * @Route("/api/v1/token/refresh", name="refresh", methods={"POST"})
    *     @SWG\Post(
    *     path="/api/v1/token/refresh",
    *     summary="Refresh token",
    *     tags={"Refresh Token"},
    *     produces={"application/json"},
    *     consumes={"application/json"},
    *     @SWG\Parameter(
    *          name="body",
    *          in="body",
    *          required=true,
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="refresh_token",
    *                  type="string"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=200,
    *          description="Token refreshed",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="token",
    *                  type="string"
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
    *     )
    * )
    */
    public function refresh(Request $request, RefreshToken $refreshService)
    {
        return $refreshService->refresh($request);
    }

    /**
     * @Route("api/v1/courses", name="courses", methods={"GET", "POST"})
     * @SWG\Get(
     *    path="/api/v1/courses",
     *    summary="Get courses",
     *    tags={"Courses"},
     *    produces={"application/json"},
     *    @SWG\Response(
     *        response=200,
     *        description="Successful fetch courses",
     *        @SWG\Schema(
     *             @SWG\Property(
     *                 property="code",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="type",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="price",
     *                 type="number"
     *             )
     *          )
     *    )
     * ),
     * @SWG\Post(
    *     path="/api/v1/courses",
    *     summary="Create new course",
    *     tags={"Create course"},
    *     produces={"application/json"},
    *     consumes={"application/json"},
    *     @SWG\Parameter(
    *          name="body",
    *          in="body",
    *          required=true,
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="code",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="title",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="type",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="price",
    *                  type="number"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=201,
    *          description="Course created",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="success",
    *                  type="boolean"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=400,
    *          description="Bad request",
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
    * @Security(name="Bearer")
    */
    public function courses(Request $request, ValidatorInterface $validator)
    {
        $response = new Response();

        if ($request->isMethod('GET')) {
            $courses = $this->getDoctrine()->getRepository(Course::class)->findAllCourses();

            $response->setContent($courses);
            $response->setStatusCode(Response::HTTP_OK);
        } elseif ($request->isMethod('POST')) {
            if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $serializer = SerializerBuilder::create()->build();
                $courseDto = $serializer->deserialize($request->getContent(), CourseFormModel::class, 'json');

                $errors = $validator->validate($courseDto);

                if (count($errors) > 0) {
                    $jsonErrors = [];
                    foreach ($errors as $error) {
                        array_push($jsonErrors, $error->getMessage());
                    }

                    $response->setContent(json_encode(['code' => 400, 'message' => $jsonErrors]));
                    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                } elseif ($this->getDoctrine()->getRepository(Course::class)->findBy(['code' => $courseDto->code])) {
                    $response->setContent(json_encode(['code' => 400, 'message' => 'Course code must be unique']));
                    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                } else {
                    $course = Course::fromDto(null, $courseDto);
    
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($course);
                    $entityManager->flush();
        
                    $response->setContent(json_encode(['success' => true]));
                    $response->setStatusCode(Response::HTTP_CREATED);
                }
            } else {
                throw new HttpException(403, 'Access denied');
            }
        }
        return $response;
    }

    /**
     * @Route("api/v1/courses/{code}", name="course", methods={"GET", "POST", "DELETE"})
     * @SWG\Get(
     *    path="/api/v1/courses/{code}",
     *    summary="Get course",
     *    tags={"Course"},
     *    produces={"application/json"},
     *    @SWG\Response(
     *        response=200,
     *        description="Successful fetch course",
     *        @SWG\Schema(
     *             @SWG\Property(
     *                 property="code",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="type",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="price",
     *                 type="number"
     *             )
     *          )
     *       )
     * ),
     * @SWG\Delete(
     *    path="/api/v1/courses/{code}",
     *    summary="Delete course",
     *    tags={"Delete course"},
     *    produces={"application/json"},
     *    @SWG\Response(
     *        response=200,
     *        description="Successful delete course",
     *        @SWG\Schema(
     *             @SWG\Property(
     *                 property="success",
     *                 type="boolean"
     *             )
     *          )
     *     ),
     *    @SWG\Response(
     *          response=400,
     *          description="Bad request",
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
     * ),
     * @SWG\Post(
    *     path="/api/v1/courses/{code}",
    *     summary="Update course",
    *     tags={"Update course"},
    *     produces={"application/json"},
    *     consumes={"application/json"},
    *     @SWG\Parameter(
    *          name="body",
    *          in="body",
    *          required=true,
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="code",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="title",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="type",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="price",
    *                  type="number"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=200,
    *          description="Course updated",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="success",
    *                  type="boolean"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=400,
    *          description="Bad request",
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
    * @Security(name="Bearer")
     */
    public function course($code, Request $request, ValidatorInterface $validator)
    {
        $response = new Response();
        
        if ($request->isMethod('GET')) {
            $course = $this->getDoctrine()->getRepository(Course::class)->findCourseByCode($code);
            
            $response->setContent($course);
            $response->setStatusCode(Response::HTTP_OK);
        } elseif ($request->isMethod('POST')) {
            if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $serializer = SerializerBuilder::create()->build();
                $courseDto = $serializer->deserialize($request->getContent(), CourseFormModel::class, 'json');
                $errors = $validator->validate($courseDto);
                if (count($errors) > 0) {
                    $jsonErrors = [];
                    foreach ($errors as $error) {
                        array_push($jsonErrors, $error->getMessage());
                    }
                    $response->setContent(json_encode(['code' => 400, 'message' => $jsonErrors]));
                    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                } elseif ($code != $courseDto->code) {
                    if ($this->getDoctrine()->getRepository(Course::class)->findBy(['code' => $courseDto->code])) {
                        $response->setContent(json_encode(['code' => 400, 'message' => 'Course code must be unique']));
                        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                    }
                } else {
                    $foundCourse = $this->getDoctrine()->getRepository(Course::class)->findOneBy(['code' => $code]);
                    $course = Course::fromDto($foundCourse, $courseDto);
    
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($course);
                    $entityManager->flush();
        
                    $response->setContent(json_encode(['success' => true]));
                    $response->setStatusCode(Response::HTTP_OK);
                }
            } else {
                throw new HttpException(403, 'Access denied');
            }
        } elseif ($request->isMethod('DELETE')) {
            if (in_array('ROLE_SUPER_ADMIN', $this->getUser()->getRoles())) {
                $course = $this->getDoctrine()->getRepository(Course::class)->findOneBy(['code' => $code]);
                if (!$course) {
                    $response->setContent(json_encode(['code' => 400, 'message' => 'Course not found']));
                    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                } else {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->remove($course);
                    $entityManager->flush();

                    $response->setContent(json_encode(['success' => true]));
                    $response->setStatusCode(Response::HTTP_OK);
                }
            } else {
                throw new HttpException(403, 'Access denied');
            }
        }

        return $response;
    }

    /**
     * @Route("api/v1/transactions", name="transactions", methods={"GET"})
     * @SWG\Get(
     *    path="/api/v1/transactions",
     *    summary="Get transactions",
     *    tags={"Transactions"},
     *    produces={"application/json"},
     *    @SWG\Parameter(
     *     name="type",
     *     in="query",
     *     type="string",
     *     description="filter type"
     * ),
     *    @SWG\Parameter(
     *     name="course_code",
     *     in="query",
     *     type="string",
     *     description="filter course_code"
     * ),
     *   @SWG\Parameter(
     *     name="skip_expired",
     *     in="query",
     *     type="boolean",
     *     description="filter skip_expired"
     * ),
     *    @SWG\Response(
     *        response=200,
     *        description="Successful fetch transactions",
     *        @SWG\Schema(
     *             @SWG\Property(
     *                 property="id",
     *                 type="number"
     *             ),
     *             @SWG\Property(
     *                 property="created_at",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="type",
     *                 type="string"
     *             ),
     *             @SWG\Property(
     *                 property="value",
     *                 type="number"
     *             ),
     *          )
     *    ),
     *    @SWG\Response(
     *        response="401",
     *        description="Unauthorized user",
     *    )
     * )
     */
    public function transactions(Request $request)
    {
        $user = $this->getUser();

        $courseCode = $request->query->get('course_code');
        $type = $request->query->get('type');
        $skipExpired = $request->query->get('skip_expired');

        $transactions = $this->getDoctrine()->getRepository(Transaction::class)->findAllTransactions($user, $courseCode, $type, $skipExpired);

        $response = new Response();
 
        $response->setContent($transactions);
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }

    /**
    * @Route("api/v1/courses/{code}/pay", name="course_pay", methods={"POST"})
    * @SWG\Post(
    *     path="/api/v1/courses/{code}/pay",
    *     summary="Pay for course",
    *     tags={"Pay for course"},
    *     produces={"application/json"},
    *     consumes={"application/json"},
    *     @SWG\Response(
    *          response=200,
    *          description="Payment Successful",
    *          @SWG\Schema(
    *              @SWG\Property(
    *                  property="success",
    *                  type="boolean"
    *              ),
    *              @SWG\Property(
    *                  property="course_type",
    *                  type="string"
    *              ),
    *              @SWG\Property(
    *                  property="exrires_at",
    *                  type="string"
    *              )
    *          )
    *     ),
    *     @SWG\Response(
    *          response=400,
    *          description="Not enough money",
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
    *         response="401",
    *         description="Unauthorized user",
    *     )
    * )
    *  @Security(name="Bearer")
    */
    public function coursePay($code, PaymentService $paymentService)
    {
        $user = $this->getUser();

        $response = new Response();
        $response->setContent($paymentService->paymentTransaction($user->getId(), $code));
        $response->setStatusCode(Response::HTTP_OK);

        return $response;
    }
}
