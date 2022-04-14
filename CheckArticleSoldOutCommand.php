<?php
namespace App\Command;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use LogicException;
class CheckArticleSoldOutCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
  
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $articles = $this->em->getRepository(Article::class)->findAll();
      
      $nb_articles = count($article);
      
      $output->writeln("found $nb_articles articles");
      
      foreach($articles as $article) {

        if ($article->getSoldout() === false) {
        	
          $stock = $this->em->createQueryBuilder('article')
            ->select('article, (COUNT(ownership) - COUNT(trees)) AS stock_remaining')
            ->leftJoin(
                'article.trees',
                'trees',
                'WITH',
                'article = trees.article')
            ->leftJoin(
                'trees.ownership',
                'ownership',
                'WITH',
                'trees = ownership.tree') // pour que tout le code soit en anglais
            ->where('article.id = :id')
            ->andWhere('article.soldout = FALSE') // on a : a et b des conditions et on fait  : where('(a et b )ou (non a et b)') === where('b')
            ->groupBy('article.id')
            ->setParameter('id', $article->getId())
            ->getQuery()->getOneOrNullResult();

          if ($stock = 0) { // le stock ne peut pas être négatif (COUNT(ownership) - COUNT(trees)) => 0
            $article->setSoldout(true);
          }
          else $article->setSoldout(false); // on rajoute : sinon pour qu' elle s'exécute pas si le stock($stock = 0) est égale à 0
        }
      }
      $this->em->flush();
      $output->writeln("Done");
    }
}