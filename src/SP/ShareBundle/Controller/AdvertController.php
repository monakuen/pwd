<?php
/**
 * Created by PhpStorm.
 * User: jerome
 * Date: 9/11/17
 * Time: 15:31
 */

namespace SP\ShareBundle\Controller;

// N'oubliez pas ce use :
use SP\ShareBundle\SPShareBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use SP\ShareBundle\Entity\Advert;
use SP\ShareBundle\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class AdvertController extends Controller
{
    public function indexAction(Request $request, $page)
    {
        // On récupère le repository
        $em = $this->getDoctrine()->getManager();

        // On récupère l'entité correspondante à l'id $id
        $listAdverts = $em->getRepository('SPShareBundle:Advert')->findBy(
            array(),                            // Critere
            array('date' => 'desc'),            // Tri
            null,                             // Limite
            null
        );
        $adverts = $this->get('knp_paginator')->paginate(
            $listAdverts,
            $request->query->get('page', $page),
            5
        );

        return $this->render('SPShareBundle:Advert:index.html.twig', array(
            'adverts' => $adverts
        ));
    }

    public function searchAction(Request $request, $page){

        $searchInput = $request->get('search-input');

        $listAdverts = $this->getDoctrine()->getManager()->getRepository('SPShareBundle:Advert')->createQueryBuilder('sp')
            ->where('sp.title LIKE :title')
            ->setParameter('title', '%'.$searchInput.'%')
            ->getQuery()
            ->getResult();

        $adverts = $this->get('knp_paginator')->paginate(
            $listAdverts,
            $request->query->get('page', $page),
            5
        );

        return $this->render('SPShareBundle:Advert:search.html.twig', array(
            'adverts' => $adverts
        ));
    }

    public function viewAction($id)
    {
        if($id<1){
            throw new NotFoundHttpException("la page est inexistante");
        }

        // On récupère le repository
        $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository('SPShareBundle:Advert')
        ;

        // On récupère l'entité correspondante à l'id $id
        $advert = $repository->find($id);

        // $advert est donc une instance de OC\PlatformBundle\Entity\Advert
        // ou null si l'id $id  n'existe pas, d'où ce if :
        if (null === $advert) {
            throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
        }

        return $this->render('SPShareBundle:Advert:view.html.twig', array(
            'advert' => $advert
        ));


    }

    public function userAction(Request $request, $username , $page){

        // On récupère le repository
        $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository('SPShareBundle:Advert')
        ;

        $author = $repository->findOneBy(array('author' => $username));

        // On récupère l'entité correspondante à l'id $id
        $listAdverts = $repository->findBy(
            array('author' => $username),       // Critere
            array('date' => 'desc'),            // Tri
            null,                             // Limite
            null                             // Offset
        );

        $adverts = $this->get('knp_paginator')->paginate(
            $listAdverts,
            $request->query->get('page', $page),
            5
        );

        return $this->render('SPShareBundle:Advert:user.html.twig', array(
            'adverts' => $adverts,
            'author' => $author
        ));
    }

    public function addAdvertAction(Request $request){
        // Création de l'entité
        $advert = new Advert();

        // On crée le FormBuilder grâce au service form factory
        $form = $this->get('form.factory')->createBuilder(FormType::class, $advert)
            ->add('title',     TextType::class)
            ->add('content',   TextareaType::class)
            ->add('image',     FileType::class)
            ->add('save',      SubmitType::class)
            ->getForm()
        ;

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            // On enregistre notre objet $advert dans la base de données, par exemple

            $file = $advert->getImage();
            // Generate a unique name for the file before saving it
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            $file->move(
                $this->getParameter('image_directory'),
                $fileName
            );

            $advert->setImage($fileName);
            $user = $this->getUser();
            $advert->setAuthor($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');
            // On redirige vers la page de visualisation de l'annonce nouvellement créée
            return $this->redirectToRoute('sp_share_view', array('id' => $advert->getId()));
            }

        return $this->render('SPShareBundle:Advert:add.html.twig', array(
            'form' => $form->createView(),
            'advert'=>$advert
        ));
    }

    public function editAdvertAction($id , Request $request){

        // On récupère le repository
        $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository('SPShareBundle:Advert')
        ;

        // On récupère l'entité correspondante à l'id $id
        $advert = $repository->find($id);

        // On crée le FormBuilder grâce au service form factory
        $form = $this->get('form.factory')->createBuilder(FormType::class, $advert)
            ->add('title',     TextType::class)
            ->add('content',   TextareaType::class)
            ->add('image',     FileType::class, array('data_class'=>null))
            ->add('save',      SubmitType::class)
            ->getForm()
        ;

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            // On enregistre notre objet $advert dans la base de données, par exemple

            $file = $advert->getImage();
            // Generate a unique name for the file before saving it
            $fileName = md5(uniqid()).'.'.$file->guessExtension();

            $file->move(
                $this->getParameter('image_directory'),
                $fileName
            );

            $advert->setImage($fileName);

            $em = $this->getDoctrine()->getManager();
            $em->persist($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');
            // On redirige vers la page de visualisation de l'annonce nouvellement créée
            return $this->redirectToRoute('sp_share_view', array('id' => $advert->getId()));
        }

        return $this->render('SPShareBundle:Advert:edit.html.twig', array(
            'form' => $form->createView(),
            'advert' => $advert
        ));
    }

    public function deleteAdvertAction($id){
        $em = $this->getDoctrine()->getManager();
        // On récupère le repository
        $advert = $em->find('SPShareBundle:Advert' , $id);

        $em->remove($advert);
        $em->flush();

        return $this->redirectToRoute('sp_share_home');
    }

    public function messageAction(){
        return new Response("cette page va afficher la liste des messages recu");
    }
}