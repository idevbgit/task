<?php
    namespace App\Controller;

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Annotation\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Doctrine\ORM\EntityManagerInterface;
    use Doctrine\ORM\Query;
    use Symfony\Component\Serializer\Encoder\CsvEncoder;
    use Symfony\Component\Serializer\Serializer;

    use App\Entity\Project;
    use App\Entity\Timelog;
    use App\Stats\Stats;
    use App\Form\Form\CreateProjectForm;
    use App\Form\Model\CreateProjectModel;

    class ProjectsController extends AbstractController
    {
        /**
         * @Route("/", name="show_projects")
         * The method below shows all existing projects and
         * creates project from the form
         */
        public function index(EntityManagerInterface $entityManager, Request $request): Response
        {
            $model = new CreateProjectModel();
            $form = $this->createForm(CreateProjectForm::class, $model);
            $form->handleRequest($request);   
            if($form->isSubmitted() && $form->isValid()) {
                $project = new Project();
                $project->setName(
                    $model->getName()
                );
                $entityManager->persist($project);
                $entityManager->flush();

                return $this->redirectToRoute('show_projects');
            }

            $projects = $entityManager->getRepository(Project::class)->createQueryBuilder('c')->getQuery()->getResult(Query::HYDRATE_ARRAY);
            
            $stats = new Stats($entityManager);
            $statsByDay = $stats->createStats(30, 'day');

            return $this->render('projects/index.html.twig', array(
                "projects" => $projects,
                "form" => $form,
                "stats" => $statsByDay
            ));
        }

        /**
         * @Route("/project/{id}", name="show_project_timelogs", methods={"GET"})
         * The method below shows all existing timelogs in the project
         */
        public function show(EntityManagerInterface $entityManager, int $id) {
            $project = $entityManager->getRepository(Project::class)->find($id*1);
            $timelogs = $entityManager->getRepository(Timelog::class)->findBy(array("project_id" => $id*1));

            return $this->render('projects/timelogs.html.twig', array(
                "timelogs" => $timelogs,
                "project" => $project
            ));
        }

        /**
         * @Route("/stats/{type}/{number}", name="show_stats", methods={"GET"})
         * The method below returns an array of statistics
         */
        public function stats(EntityManagerInterface $entityManager, $type, $number) {
            $stats = new Stats($entityManager);
            $statsByType = $stats->createStats($number*1, $type);
            return new Response(json_encode($statsByType));
        }

        /**
         * @Route("/stats/csv", name="send_stats_csv", methods={"GET"})
         * The method below sends a csv file with hourly statistics about the past year by months
         */
        public function statsCSV(EntityManagerInterface $entityManager) {
            $stats = new Stats($entityManager);
            $statsByMonth = $stats->createStats(12, 'month');

            $csvEncoder = new CsvEncoder();
            $arrayToCsv = $stats->prepareStatsForCsv($statsByMonth);
            $csvContent = $csvEncoder->encode($arrayToCsv, 'csv', ['Total hours per month']);

            $response = new Response($csvContent);
            $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
            $response->headers->set('Content-Encoding', 'UTF-8');
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename=stats.csv');
            return $response;
        }
    }
?>