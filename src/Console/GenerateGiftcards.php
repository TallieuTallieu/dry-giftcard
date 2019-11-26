<?php

namespace Tnt\Giftcard\Console;

use dry\media\Directory;
use dry\media\File;
use dry\Template;
use Oak\Console\Command\Command;
use Oak\Console\Command\Signature;
use Oak\Contracts\Config\RepositoryInterface;
use Oak\Contracts\Console\InputInterface;
use Oak\Contracts\Console\OutputInterface;
use Oak\Contracts\Container\ContainerInterface;
use Oak\Contracts\Dispatcher\DispatcherInterface;
use Tnt\Giftcard\Events\Generated;
use Tnt\Giftcard\Model\Giftcard;
use Tnt\PdfGen\PdfGenerator;

class GenerateGiftcards extends Command
{
    /**
     * @var RepositoryInterface $config
     */
    private $config;

    /**
     * @var DispatcherInterface $dispatcher
     */
    private $dispatcher;

    /**
     * GenerateGiftcards constructor.
     * @param ContainerInterface $app
     * @param RepositoryInterface $config
     * @param DispatcherInterface $dispatcher
     */
    public function __construct(ContainerInterface $app, RepositoryInterface $config, DispatcherInterface $dispatcher)
    {
        $this->config = $config;
        $this->dispatcher = $dispatcher;

        parent::__construct($app);
    }

    protected function createSignature(Signature $signature): Signature
    {
        return $signature->setName('generate-giftcards')
            ->setDescription('Generate giftcard PDFs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $templateFilename = $this->config->get('giftcard.template_filename', 'app/templates/giftcard/pdf.tpl');

        $directory = dirname($templateFilename).'/';
        $filename = basename($templateFilename);

        $giftcards = Giftcard::all('WHERE status = ?', Giftcard::STATUS_AWAITING_GENERATION);

        $generatedAmount = 0;

        foreach ($giftcards as $giftcard) {

            $tpl = new Template();
            $tpl::$template_directory = $directory;
            $tpl->giftcard = $giftcard;

            // We create a new PdfGenerator instance for each gift card that needs to be generated
            // We'd rather inject the PdfGenerator, but an instance of Dompdf can only generate 1 pdf when using fromHtml
            $pdfGenerator = $this->app->get(PdfGenerator::class);

            // Generate the giftcard pdf
            $pdfContents = $pdfGenerator->fromHtml($tpl->get($filename))->output();

            $file = File::import($giftcard->discount->code.'-giftcard.pdf', 'application/pdf', $pdfContents);

            $dir = Directory::get('Giftcards/'.date('Y/F'));
            $file->directory = $dir;
            $file->save();

            // Save the file to the giftcard
            $giftcard->file = $file;
            $giftcard->status = Giftcard::STATUS_READY;
            $giftcard->save();

            // Dispatch a Generated-event
            $this->dispatcher->dispatch(Generated::class, new Generated($giftcard));
            $generatedAmount++;

            $output->writeLine($giftcard->from.' - '.$giftcard->to);
        }

        $output->writeLine($generatedAmount.' gift card pdfs generated');
    }
}