<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use JMS\Serializer\SerializerBuilder;

/**
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function findAllCourses()
    {
        $courses = $this->createQueryBuilder('c')
            ->select('c.code', 'c.type', 'c.price')
            ->getQuery()
            ->getResult();
        
        if (!$courses) {
            return json_encode(['message' => 'No courses found']);
        } else {
            for ($i = 0; $i < count($courses); $i++) {
                if ($courses[$i]['type'] == 0) {
                    $courses[$i]['type'] = "rent";
                } elseif ($courses[$i]['type'] == 1) {
                    $courses[$i]['type'] = 'buy';
                } elseif ($courses[$i]['type'] == 2) {
                    $courses[$i]['type'] = 'free';
                    unset($courses[$i]['price']);
                }
            }

            $serializer = SerializerBuilder::create()->build();
            return $serializer->serialize($courses, 'json');
        }
    }

    public function findCourseByCode($code)
    {
        $course = $this->createQueryBuilder('c')
            ->select('c.code', 'c.type', 'c.price')
            ->where('c.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult();

        if (!$course) {
            return json_encode(['message' => 'No course found']);
        } else {
            for ($i = 0; $i < count($course); $i++) {
                if ($course[$i]['type'] == 0) {
                    $course[$i]['type'] = "rent";
                } elseif ($course[$i]['type'] == 1) {
                    $course[$i]['type'] = 'buy';
                } elseif ($course[$i]['type'] == 2) {
                    $course[$i]['type'] = 'free';
                    unset($course[$i]['price']);
                }
            }

            $serializer = SerializerBuilder::create()->build();
            return $serializer->serialize($course, 'json');
        }
    }
}
