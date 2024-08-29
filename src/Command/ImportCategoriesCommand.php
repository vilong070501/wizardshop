<?php

namespace App\Command;

use App\Entity\CategoryShop;
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
    name: 'app:import:categories',
    description: 'Import categories',
)]
class ImportCategoriesCommand extends Command
{
    const IMPORT_FOLDER = 'var/init/import/';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
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

        $this->dropExistingCategories($io);

        if (($file = fopen($filePath, 'r')) === false) {
            $io->error('Cannot open file: ' . $filePath);
            return Command::FAILURE;
        }

        fgetcsv($file, 1000, ",");  // Skip the header row
        while (($header = fgetcsv($file, 1000, ",")) !== false) {
            // Assuming the CSV columns: Name, Slug
            $categoryShop = new CategoryShop();
            $categoryShop->setName($header[0])
                ->setSlug($header[1]);

            $this->entityManager->persist($categoryShop);
        }

        fclose($file);
        $this->entityManager->flush();

        $io->success('Categories imported successfully');

        return Command::SUCCESS;
    }

    private function dropExistingCategories(SymfonyStyle $io): void
    {
        $categories = $this->entityManager->getRepository(CategoryShop::class)->findAll();

        foreach ($categories as $category) {
            $this->entityManager->remove($category);
        }

        $this->entityManager->flush();

        $io->success('All existing categories shop have been deleted');
    }
}
