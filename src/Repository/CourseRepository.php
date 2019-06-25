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
    const RENT_COURSE = 0;
    const BUY_COURSE = 1;
    const FREE_COURSE = 2;

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
                switch ($courses[$i]['type']) {
                    case self::RENT_COURSE:
                        $courses[$i]['type'] = "rent";
                        break;
                    case self::BUY_COURSE:
                        $courses[$i]['type'] = "buy";
                        break;
                    case self::FREE_COURSE:
                        $courses[$i]['type'] = "free";
                        unset($courses[$i]['price']);
                        break;
                }
            }

            return $courses;
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
                switch ($course[$i]['type']) {
                    case self::RENT_COURSE:
                        $course[$i]['type'] = "rent";
                        break;
                    case self::BUY_COURSE:
                        $course[$i]['type'] = "buy";
                        break;
                    case self::FREE_COURSE:
                        $course[$i]['type'] = "free";
                        unset($course[$i]['price']);
                        break;
                }
            }

            
            return $course;
        }
    }
}
