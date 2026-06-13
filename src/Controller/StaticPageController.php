<?php

namespace App\Controller;

use App\DTO\ContactDTO;
use App\Form\ContactType;
use App\Repository\TableauRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class StaticPageController extends AbstractController
{
    #[Route('/about', name: 'about')]
    public function index(): Response
    {
        return $this->render('static_page/about.html.twig', [
            'controller_name' => 'StaticPageController',
        ]);
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentions(): Response
    {
        return $this->render('static_page/mentions_legales.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $data = new ContactDTO();

        $form = $this->createForm(ContactType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted()  && $form->isValid()) {
            try {
                $mail = (new TemplatedEmail())
                    ->from('contact@navarroraphael.fr')
                    ->replyTo($data->email)
                    ->to('contact@navarroraphael.fr')
                    ->bcc('navarroraphael@yahoo.fr') // alerte discrète
                    ->subject('Demande de contact')
                    ->htmlTemplate('emails/contact.html.twig')
                    ->context(['data' => $data]);
                $mailer->send($mail);
                $this->addFlash('success', 'Votre email a bien été envoyé');
                return $this->redirectToRoute('contact');
            } catch (\Exception $e) {
                dump($e->getMessage());
                die();
                // //indiquer le type d'erreur
                // $this->addFlash('danger', 'Impossible d\'envoyer votre mail');
                // return $this->redirectToRoute('contact');
            }
        }
        return $this->render('contact/contact.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route("/", name: 'home')]
    public function indexHome(
        TableauRepository $tableauRepository
    ): Response {
        // On récupère seulement les tableaux activés dans le slider
        $tableaux = $tableauRepository->findBy(['is_in_slider' => true]);

        if (!$tableaux) {
            throw $this->createNotFoundException('Tableaux non trouvés');
        }

        return $this->render('home/index.html.twig', [
            'tableaux' => $tableaux
        ]);
    }
}
