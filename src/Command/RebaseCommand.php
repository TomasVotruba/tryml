<?php

declare(strict_types=1);

namespace TomasVotruba\Tryml\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TomasVotruba\Tryml\ProcessRunner;
use Webmozart\Assert\Assert;

final class RebaseCommand extends Command
{
    public function __construct(
        private readonly SymfonyStyle $symfonyStyle,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('typed-services');
        $this->setDescription('Move named services to class-typed');

        $this->addOption('branch-prefix', null, InputOption::VALUE_REQUIRED, 'Branch prefix to rebase');

        $this->addOption('main-branch', null, InputOption::VALUE_REQUIRED, 'Name of the main branch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $branchPrefix = $this->resolveBranchPrefix($input);
        $mainBranch = $this->resolveMainBranch($input);

        $branchList = $this->resolvePrefixedBranchList($branchPrefix);

        $titleMessage = sprintf(
            'For branch prefix "%s" we found %d branch%s',
            $branchPrefix,
            count($branchList),
            count($branchList) !== 1 ? 'es' : ''
        );
        $this->symfonyStyle->title($titleMessage);

        if ($branchList === []) {
            $this->symfonyStyle->warning('No branches to rebase');
            return self::SUCCESS;
        }

        $this->symfonyStyle->listing($branchList);

        dump($mainBranch);

        return self::SUCCESS;
    }

    private function resolveBranchPrefix(InputInterface $input): string
    {
        $branchPrefix = $input->getOption('branch-prefix');
        Assert::notEmpty($branchPrefix, 'Fill the branch prefix, e.g. "--branch-prefix tv-"');

        return $branchPrefix;
    }

    private function resolveMainBranch(InputInterface $input): string
    {
        $mainBranch = $input->getOption('main-branch');
        Assert::notEmpty($mainBranch, 'Fill main branch, e.g. "--main-branch main"');

        return $mainBranch;
    }

    /**
     * @return string[]
     */
    private function resolvePrefixedBranchList(string $branchPrefix): array
    {
        $prefixedBranchesGitCommand = sprintf("git branch | grep '^\*\? *%s'", $branchPrefix);
        $branchListOutput = ProcessRunner::run($prefixedBranchesGitCommand);
        $branchList = explode("\n", $branchListOutput);

        Assert::isList($branchList);
        Assert::allString($branchList);

        return $branchList;
    }
}
