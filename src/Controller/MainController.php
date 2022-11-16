<?php
namespace App\Controller;

use App\Entity\Articles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ArticlesRepository;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="articles")
     *
     * @param ArticlesRepository $articlesRepository
     *
     * @return Response
     */
    public function index(Request $request, ArticlesRepository $articlesRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $is_admin = in_array('admin', $user->getRoles());

        // get articles cunt
        $count = $articlesRepository->createQueryBuilder('a')->select('count(a.id)')->getQuery()->getSingleScalarResult();

        // offset and limit values for pagination, defaults to 10 and 0
        $limit = max(0, $request->query->getInt('limit', 10));
        $offset = max(0, $request->query->getInt('offset', 0));
        
        $paginator = $articlesRepository->getPaginator($limit, $offset);

        return $this->render('index.html.twig', [
            'articles' => $articlesRepository->findBy(array(), array('id' => 'DESC'), $limit, $offset),
            'count' => $count,
            'user' => $user,
            'previous' => $offset - $limit,
            'next' => min(count($paginator), $offset + $limit),
            'offset' =>$offset,
            'is_admin' => $is_admin
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete_article")
     *
     * @param Articles $article
     *
     * @return RedirectResponse
     */
    public function deleteBlog(Articles $article): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        $user_roles = $user->getRoles();

        // check if the user has admin priviledges
        if(!in_array("admin", $user_roles)){
            $this->addFlash('error', 'Cannot delete article. Insufficient permissions');
            return $this->redirectToRoute('articles');
        }

        // delete the post from the db
        $manager = $this->container->get('doctrine')->getManager();
        $manager->remove($article);
        $manager->flush();

        $this->addFlash('success', 'Article deleted!');
        return $this->redirectToRoute('articles');
    }
}