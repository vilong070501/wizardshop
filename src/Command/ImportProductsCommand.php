<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\CategoryShopRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:products',
    description: 'Import products',
)]
class ImportProductsCommand extends Command
{
    const IMPORT_FOLDER='var/init/import/';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategoryShopRepository $categoryShopRepository,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the import csv file')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = static::IMPORT_FOLDER . $input->getArgument('file');

        if (!file_exists($filePath)) {
            $io->error('File not found');
            return Command::FAILURE;
        }

        if (($file = fopen($filePath, 'r')) === false) {
            $io->error('Cannot open file: ' . $filePath);
            return Command::FAILURE;
        }

        fgetcsv($file, 1000, ",");  // Skip the header row
        while (($header = fgetcsv($file, 1000, ",")) !== false) {
            // Assuming the CSV columns: Title, Slug, Content, Online, Subtitle, Price, CategoryName
            $product = new Product();
            $product->setTitle($header[0])
                ->setSlug($header[1])
                ->setContent($header[2])
                ->setOnline((bool)$header[3])
                ->setSubtitle($header[4])
                ->setPrice((float)$header[5])
                ->setCreatedAt(new DateTime('now'));

            $category = $this->categoryShopRepository->findOneBy(['name' => $header[6]]);
            if ($category) {
                $product->setCategory($category);
            } else {
                $io->error("Category '$header[6]' not found for product '$header[0]'");
            }

            $this->entityManager->persist($product);
        }

        fclose($file);
        $this->entityManager->flush();

        $io->success('Products imported successfully');

        return Command::SUCCESS;
    }
}
