<?php

    namespace App\Form\Model;

    class CreateProjectModel {
        private $name;

        public function getName() {
            return $this->name;
        }

        public function setName($name): void {
            $this->name = $name;
        }
    }

?>