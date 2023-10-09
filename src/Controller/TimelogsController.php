<?php
    namespace App\Controller;

    use App\Entity\Timelog;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Doctrine\ORM\EntityManagerInterface;

    /**
     * The TimelogsController class implements an application that
     * could create, change and delete timelogs
     */

    class TimelogsController extends AbstractController
    {
        /**
         * @Route("/timelog", name="create_timelog", methods={"POST"})
         * The method below creates timelog with project_id and current timestamp(as start_time)
         */
        public function start(EntityManagerInterface $entityManager, Request $request): Response {
            $data = $request->request->all();
            $timelog = new Timelog();

            $time = time();
            $timelog->setProjectId($data['project_id']*1);
            $timelog->setStartTime($time);

            $entityManager->persist($timelog);
            $entityManager->flush();

            return new Response(json_encode([
                "success" => true,
                "id" => $timelog->getId(),
                "date" => date('Y-m-d H:i:s', $time)
            ]));
        }

        /**
         * @Route("/timelog/{id}", name="setup_timelog_finish_time", methods={"POST"})
         * The method below determines end time of the timelog as the current timestamp
         */
        public function stop(EntityManagerInterface $entityManager, int $id): Response {
            $time = time();
            $timelog = $entityManager->getRepository(Timelog::class)->find($id*1);
            
            if(!empty($timelog) && !$timelog->getFinishTime()) {
                $timelog->setFinishTime($time);

                $entityManager->persist($timelog);
                $entityManager->flush();

                return new Response(json_encode([
                    "success" => true,
                    "date" => date('Y-m-d H:i:s', $time)
                ]));
            } else {
                return new Response(json_encode([
                    "success" => false
                ]));
            }
            
        }

        /**
         * @Route("/timelog/{id}", name="delete_timelog_by_id", methods={"DELETE"})
         * The method below delete timelog
         */ 
        public function delete(EntityManagerInterface $entityManager, int $id): Response {
            $timelog = $entityManager->getRepository(Timelog::class)->find($id*1);

            if(!empty($timelog)) {
                $entityManager->remove($timelog);
                $entityManager->flush();
                return new Response(json_encode([
                    "success" => true
                ]));
            } else {
                return new Response(json_encode([
                    "success" => false
                ]));
            }
            
        }

        /**
         * @Route("/timelog/{id}", name="edit_timelog_by_id", methods={"PATCH"})
         * The method below make changes in the timelog accourding received json data
         * especially $start_time, $finish_time
         */
        public function edit(EntityManagerInterface $entityManager, Request $request, int $id): Response {
            $data = $request->request->all();
            $timelog = $entityManager->getRepository(Timelog::class)->find($id*1);

            $start_time = strtotime($data['start_time']);
            $finish_time = isset($data['finish_time']) ? strtotime($data['finish_time']) : 0;

            $dataCorrect = $start_time && (
                (
                    ($timelog->getFinishTime() && $finish_time) && ($start_time < $finish_time) && ($finish_time < time())
                ) || (
                    !$timelog->getFinishTime() && !$finish_time
                )
            );

            if(!empty($timelog) && $dataCorrect) {
                $timelog->setStartTime($start_time);
                if($finish_time)
                    $timelog->setFinishTime($finish_time);

                $entityManager->persist($timelog);
                $entityManager->flush();

                return new Response(json_encode([
                    "success" => true
                ]));
            } else {
                return new Response(json_encode([
                    "success" => false
                ]));
            }
        }
    }
?>