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

#[Route('/task')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'task_index')]
    public function index(TaskRepository $taskRepository): Response
    {
        return $this->render('task/index.html.twig', [
            'tasks' => $taskRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'task_show', requirements: ['id' => '\d+'])]
    public function show(?Task $task): Response
    {
        if (!$task) {
            throw $this->createNotFoundException('La tâche demandée n\'existe pas.');
        }

        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }


    #[Route('/new', name: 'task_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
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
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
        }

        return $this->redirectToRoute('task_index');
    }
}
