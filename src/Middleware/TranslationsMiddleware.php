<?php

namespace App\Middleware;

use App\Manager\VisitorManager;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * Class TranslationsMiddleware
 *
 * Middleware for set locale by visitor location
 *
 * @package App\Middleware
 */
class TranslationsMiddleware
{
    private VisitorManager $visitorManager;
    private LocaleAwareInterface $translator;

    public function __construct(VisitorManager $visitorManager, LocaleAwareInterface $translator)
    {
        $this->translator = $translator;
        $this->visitorManager = $visitorManager;
    }

    /**
     * Set translation based on visitor location
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        $language = $this->visitorManager->getVisitorLanguage();

        // check unidentified languages
        if ($language == null or $language == 'host' or $language == 'unknown') {
            $this->translator->setLocale('en');
        } else {
            // set visitor locale
            $this->translator->setLocale($language);
        }
    }
}
