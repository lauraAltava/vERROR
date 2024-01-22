<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Empleado;
use App\Entity\Seccion;
use Symfony\Bridge\Doctrine\ManagerRegistry as DoctrineManagerRegistry;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\EmpleadoFormType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class EmpleadoController extends AbstractController
{

    #[Route('/empleado/nuevo', name: 'nuevo_empleado')]
    public function nuevo(ManagerRegistry $doctrine, Request $request){
        $empleado = new Empleado();

        $formulario = $this->createForm(EmpleadoFormType::class, $empleado);

   
            $formulario->handleRequest($request);

            if($formulario->isSubmitted() && $formulario->isValid()){
                $empleado = $formulario->getData();
                $entityManager = $doctrine->getManager();
                $entityManager -> persist($empleado);
                $entityManager->flush();
                return $this->redirectToRoute('ficha_empleado', 
                ["codigo" => $empleado->getId()]);
            }
        
        return $this->render('page/nuevo.html.twig', array(
            'formulario' => $formulario->createView()
        ));
    }


    #[Route('/empleado/buscar/{apellidos}', name: 'buscar_empleado')]
    public function buscar(ManagerRegistry $doctrine, $apellidos): Response{
        $repositorio = $doctrine->getRepository(Empleado::class);

        $empleados = $repositorio->findByName($apellidos);

        return $this->render('page/lista_empleados.html.twig', [
            'empleados' => $empleados
        ]);

    }

    #[Route('/empleado/{codigo}', name: 'ficha_empleado')]
    public function ficha(ManagerRegistry $doctrine, $codigo): Response{
        $repositorio = $doctrine->getRepository(Empleado::class);
        $empleado = $repositorio->find($codigo);

        return $this->render('page/ficha_empleado.html.twig', [
            'empleado' => $empleado
        ]);

    }

    #[Route('/empleado/editar/{codigo}', name:"editar_empleado", 
    requirements:["codigo"=>"\d+"])]

    public function editar(ManagerRegistry $doctrine, Request $request, SessionInterface $session, 
    $codigo, SluggerInterface $slugger){
        $user = $this->getUser();
        
        if ($user){
        $repositorio = $doctrine->getRepository(Empleado::class);
        $empleado = $repositorio->find($codigo);

        if($empleado){
            $formulario = $this->createForm(EmpleadoFormType::class, $empleado);
            $formulario->handleRequest($request);
        }
           

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            $empleado = $formulario->getData();
            $file = $formulario->get('foto')->getData();
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        
                // Move the file to the directory where images are stored
                try {
        
                    $file->move(
                        $this->getParameter('images_directory'), $newFilename
                    );
                   
                } catch (FileException $e) {
                   
                }
                $empleado->setFoto($newFilename);
            }
               
            $entityManager = $doctrine->getManager();    
            $entityManager->persist($empleado);
            $entityManager->flush();
        }
        return $this->render('page/nuevo.html.twig', array(
            'formulario' => $formulario->createView()));
        

        }else{

            $url=$this->generateUrl('editar_empleado', ['codigo' => $codigo]);
            $session->set('enlace', $url);
            return $this->redirectToRoute('app_login');
        }
}
    #[Route('/empleado/insertar', name: 'insertar_empleado')]
    public function insertar(ManagerRegistry $doctrine)
{
    $entityManager = $doctrine->getManager();
    foreach($this->empleados as $c){
        $empleado = new Empleado();
        $empleado->setNombre($c["nombre"]);
        $empleado->setTelefono($c["apellidos"]);
        $empleado->setEmail($c["foto"]);
        $entityManager->persist($empleado);
    }
    try{
        $entityManager->flush();
        return new Response("empleados insertados");
    }catch (\Exception $e){
        return new Response("Error insertando objetos" . $e->getMessage());
    }

}

    #[Route('/empleado/delete/{id}', name: 'eliminar_empleado')]

    public function delete(ManagerRegistry $doctrine, $id, SessionInterface $session): Response{
        $user = $this->getUser();

        if ($user){
        $entityManager = $doctrine->getManager();
        $repositorio = $doctrine->getRepository(Empleado::class);
        $empleado = $repositorio->find($id);
        if ($empleado){
            try{
                $entityManager->remove($empleado);
                $entityManager->flush();
                return $this->redirectToRoute('inicio');
            }catch (\Exception $e){
                return new Response("Error eliminado objeto");
            }
        }
        }else{
            $url=$this->generateUrl(
                'eliminar_empleado', ['id' => $id]);
            $session->set('enlace', $url);
            return $this->redirectToRoute('app_login');
        }
    }
}
