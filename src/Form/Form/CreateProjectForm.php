<?php

    namespace App\Form\Form;
    
    use App\Form\Model\CreateProjectModel;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\Component\Validator\Constraints\NotBlank;
    use Symfony\Component\Validator\Constraints\Length;

    class CreateProjectForm extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options) {
            $builder->setMethod('POST');
            
            $builder->add('name', TextType::class, array(
                'required' => true, 
                'constraints' => [
                    new NotBlank()
                ],
                'attr' => array(
                    'class' => 'form-control'
                )
            ));

            $builder->add('save', SubmitType::class, array(
                'label' => 'Create',
                'attr' => array(
                    'class' => 'btn btn-primary mt-3 mb-5'
                )
            ));
        }

        public function configureOptions(OptionsResolver $resolver) {
            $resolver->setDefaults([
                'data_class' => CreateProjectModel::class
            ]);
        }
    }

?>