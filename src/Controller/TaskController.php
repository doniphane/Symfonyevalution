<?php

namespace App\Controller;
use App\Entity\Task;
use App\Form\TaskTypeForm;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
// #[Route('/')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'task_index')]
    public function index(TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder aux tâches.');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            // Si l'utilisateur est admin, il voit toutes les tâches
            $tasks = $taskRepository->findAll();
        } else {
            // Sinon, il ne voit que les siennes
            $tasks = $taskRepository->findBy(['user' => $user]);
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks
        ]);
    }

    #[Route('/{id}', name: 'task_show', requirements: ['id' => '\d+'])]
    public function show(?Task $task): Response
    {
        if (!$task) {
            throw $this->createNotFoundException('La tâche demandée n\'existe pas.');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette tâche.');
        }

        if ($task->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette tâche.');
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/new', name: 'task_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour créer une tâche.');
        }

        $task = new Task();
        $task->setUser($user);

        $form = $this->createForm(TaskTypeForm::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit')]
    public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        if (!$task) {
            throw $this->createNotFoundException('La tâche demandée n\'existe pas.');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier une tâche.');
        }

        if ($task->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette tâche.');
        }

        $form = $this->createForm(TaskTypeForm::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/form.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/{id}/delete', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        if (!$task) {
            throw $this->createNotFoundException('La tâche demandée n\'existe pas.');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour supprimer une tâche.');
        }

        if ($task->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette tâche.');
        }

        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('task_index');
    }
}