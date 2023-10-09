<?php

    namespace App\Entity;

    use App\Repository\TimelogRepository;
    use Doctrine\ORM\Mapping as ORM;

    #[ORM\Entity(repositoryClass: TimelogRepository::class)]
    class Timelog
    {
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null;

        #[ORM\Column]
        private ?int $project_id = null;

        #[ORM\Column]
        private ?int $start_time = null;

        #[ORM\Column]
        private ?int $finish_time = 0;

        public function getId(): ?int
        {
            return $this->id;
        }

        public function setId($id) {
            $this->id = $id;
        }

        public function setStartTime($timestamp) {
            $this->start_time = $timestamp;
        }

        public function getStartTime() {
            return $this->start_time;
        }

        public function setFinishTime($timestamp) {
            $this->finish_time = $timestamp;
        }

        public function getFinishTime() {
            return $this->finish_time;
        }

        public function setProjectId($projectId) {
            $this->project_id = $projectId;
        }

        public function getProjectId() {
            return $this->project_id;
        }
    }

?>