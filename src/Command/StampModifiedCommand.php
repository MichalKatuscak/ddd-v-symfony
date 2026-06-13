<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stamp-modified',
    description: 'Nastaví frontmatter `modified` každé kapitoly na datum poslední změny souboru v gitu',
)]
final class StampModifiedCommand extends Command
{
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Jen vypíše změny, nic nezapíše');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $dir = $this->projectDir . '/content/chapters';
        $files = glob($dir . '/*.md') ?: [];
        if ($files === []) {
            $io->error('Nenalezeny žádné kapitoly v ' . $dir);
            return Command::FAILURE;
        }

        $rows = [];
        $changed = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $name = basename($file);
            $gitDate = $this->gitLastModified($file);

            if ($gitDate === null) {
                $rows[] = [$name, '—', '—', '<comment>bez git historie, přeskočeno</comment>'];
                $skipped++;
                continue;
            }

            $raw = file_get_contents($file);
            if ($raw === false) {
                $rows[] = [$name, '?', $gitDate, '<error>nelze číst</error>'];
                $skipped++;
                continue;
            }

            $current = null;
            if (preg_match('/^modified:\s*"?(\d{4}-\d{2}-\d{2})"?/m', $raw, $m)) {
                $current = $m[1];
            }

            if ($current === $gitDate) {
                $rows[] = [$name, $current, $gitDate, 'beze změny'];
                continue;
            }

            // Nahraď existující řádek `modified:` při zachování formátu s uvozovkami.
            $new = preg_replace(
                '/^modified:\s*"?\d{4}-\d{2}-\d{2}"?.*$/m',
                'modified: "' . $gitDate . '"',
                $raw,
                1,
            );

            if ($new === null || $new === $raw && $current !== null) {
                $rows[] = [$name, $current ?? '—', $gitDate, '<error>nelze nahradit</error>'];
                $skipped++;
                continue;
            }

            if ($current === null) {
                $rows[] = [$name, '—', $gitDate, '<comment>chybí pole modified, přeskočeno</comment>'];
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                file_put_contents($file, $new);
            }
            $rows[] = [$name, $current, $gitDate, $dryRun ? '<info>by se změnilo</info>' : '<info>změněno</info>'];
            $changed++;
        }

        $io->table(['Kapitola', 'modified (před)', 'git datum', 'akce'], $rows);
        $io->success(sprintf(
            '%s: %d změněno, %d beze změny/přeskočeno%s',
            $dryRun ? 'Dry-run' : 'Hotovo',
            $changed,
            count($files) - $changed,
            $dryRun ? ' (nic nezapsáno)' : '',
        ));

        return Command::SUCCESS;
    }

    /** Datum (YYYY-MM-DD) posledního commitu dotýkajícího se souboru, nebo null. */
    private function gitLastModified(string $file): ?string
    {
        $cmd = sprintf(
            'git -C %s log -1 --format=%%cs -- %s 2>/dev/null',
            escapeshellarg($this->projectDir),
            escapeshellarg($file),
        );
        $out = shell_exec($cmd);
        if (!is_string($out)) {
            return null;
        }
        $out = trim($out);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $out) === 1 ? $out : null;
    }
}
