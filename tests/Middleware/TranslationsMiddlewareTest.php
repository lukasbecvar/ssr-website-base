<?php

namespace App\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use App\Manager\VisitorManager;
use App\Middleware\TranslationsMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * Class TranslationsMiddlewareTest
 *
 * Test cases for translations middleware
 *
 * @package App\Tests\Middleware
 */
class TranslationsMiddlewareTest extends TestCase
{
    private TranslationsMiddleware $middleware;
    private VisitorManager & MockObject $visitorManagerMock;
    private LocaleAwareInterface & MockObject $translatorMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->visitorManagerMock = $this->createMock(VisitorManager::class);
        $this->translatorMock = $this->createMock(LocaleAwareInterface::class);

        // create translations middleware instance
        $this->middleware = new TranslationsMiddleware(
            $this->visitorManagerMock,
            $this->translatorMock
        );
    }

    /**
     * Test set locale to english for unidentified languages
     *
     * @return void
     */
    public function testSetLocaleEnglishForUnidentifiedLanguages(): void
    {
        // mock visitor language as unidentified
        $this->visitorManagerMock->expects($this->once())->method('getVisitorLanguage')->willReturn(null);

        // expect setting locale to 'en'
        $this->translatorMock->expects($this->once())->method('setLocale')->with('en');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test set locale to english for host language
     *
     * @return void
     */
    public function testSetLocaleEnglishForHostLanguage(): void
    {
        // mock visitor language as 'host'
        $this->visitorManagerMock->expects($this->once())->method('getVisitorLanguage')->willReturn('host');

        // expect setting locale to 'en'
        $this->translatorMock->expects($this->once())->method('setLocale')->with('en');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test set locale to english for unknown languages
     *
     * @return void
     */
    public function testSetLocaleEnglishForUnknownLanguage(): void
    {
        // mock visitor language as 'unknown'
        $this->visitorManagerMock->expects($this->once())->method('getVisitorLanguage')->willReturn('unknown');

        // expect setting locale to 'en'
        $this->translatorMock->expects($this->once())->method('setLocale')->with('en');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test set visitor language locale
     *
     * @return void
     */
    public function testSetVisitorLanguageLocale(): void
    {
        // mock visitor language as 'fr' (example language code)
        $this->visitorManagerMock->expects($this->once())->method('getVisitorLanguage')->willReturn('fr');

        // expect setting locale to 'fr'
        $this->translatorMock->expects($this->once())->method('setLocale')->with('fr');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }
}
