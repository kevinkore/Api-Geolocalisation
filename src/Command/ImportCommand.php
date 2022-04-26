<?php

namespace App\Command;

use App\Entity\Common;
use App\Entity\CommunalSector;
use App\Entity\Department;
use App\Entity\District;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Romuald BOGRO <bogrolcr@gmail.com>
 */
#[AsCommand(
    name: 'import',
    description: 'importation de fichier',
)]
class ImportCommand extends Command
{

    protected EntityManagerInterface $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'files path')
            //->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
       
        $path = $input->getArgument('path');
        $io = new SymfonyStyle($input, $output);
        $prevLine = [];

        try {
            
            if (($handle = fopen($path, "r")) !== false) {
                while (($line = fgetcsv($handle, 1000, ",")) !== false) {

                    if (empty($line[0])) {
                        $line = [
                            $prevLine[0],
                            $prevLine[1],
                            $prevLine[2],
                            $prevLine[3],
                            $line[4],
                            $line[5],
                            $line[6]
                        ];
                    } else {
                        $prevLine = $line;
                    }

                    $districtName = $line[0];
                    $capital = $line[1];

                    $district = $this->manager->getRepository(District::class)->findOneByName($districtName);

                    if (!$district) {
                        $district = new District();
                        $district->setName($districtName);
                        $district->setCapital($capital);
                        $this->manager->persist($district);
                        $this->manager->flush();
                    }

                    $this->regionTreatment($district, $line[2], $line[3], function (Region $region) use ($line) {
                        
                        try {
                            $department = $this->manager->getRepository(Department::class)->findOneByName($line[4]);
                            $common = $this->manager->getRepository(Common::class)->findOneByName($line[5]);

                            if (!$department) {
                                $department = new Department();
                                $department->setName($line[4]);
                                $department->setRegion($region);
                                $this->manager->persist($department);
                            }

                            if (!$common) {
                                $common = new Common();
                                $common->setName($line[5]);
                                $common->setDepartment($department);
                                $this->manager->persist($common);
                            }
                            
                            if (true === isset($line[6])) {
                                $communalSector = $this->manager->getRepository(CommunalSector::class)->findOneByName($line[6]);

                                if (!$communalSector) {
                                    $communalSector = new CommunalSector();
                                    $communalSector->setName($line[6]);
                                    $communalSector->setCommon($common);
                                    $this->manager->persist($communalSector);
                                }
                            }
                            $this->manager->flush();

                        } catch (\Exception $e) {
                            throw new LogicException($e->getMessage());
                        }

                    });

                    $io->success('Import line');
                    
                }
                fclose($handle);
            }

        } catch (\Exception $e) {
            $io->error('Exception reçue : '.$e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Succes!. le fichier a été importer');
        return Command::SUCCESS;
    }

    protected function regionTreatment(District $district, string $regionName, string $capital, callable $callableFunction = null): Region
    {
        try {
            $region = $this->manager->getRepository(Region::class)->findOneByName($regionName);

            if (!$region) {
                $region = new Region();
                $region->setName($regionName);
                $region->setCapital($capital);
                $region->setDistrict($district);
                $this->manager->persist($region);
                $this->manager->flush();
            }

            if (null !== $callableFunction) {
                $callableFunction($region);
            }

        } catch (\Exception $e) {
            throw new LogicException($e->getMessage());
        }

        return $region;
    }
}
