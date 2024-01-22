<?php

namespace App\Form;

use App\Entity\Empleado;
use App\Entity\Seccion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\Extension\Core\Type\EmailType;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;



class EmpleadoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder

            ->add('nombre', TextType::class)
          

            ->add('apellidos', TextType::class)
          
            ->add('seccion', EntityType::class, array(

                'class' => Seccion::class,

                'choice_label' => 'nombre',))
            ->add('foto', FileType::class,[
                'mapped' => false,
                
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file',
                    ])
                ],
            ])

            
               

            ->add('save', SubmitType::class, array('label' => 'Enviar'));
          
    }

}
