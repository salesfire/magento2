<?php

namespace Salesfire\Salesfire\Helper;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class CookieHelper
{
    const COOKIE_NAME = 'sf_cuid'; // sf custom user identifier
    const COOKIE_EXPIRATION = 63072000; // 2 years

    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $sessionManager;

    public function __construct(
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
    }

    public function getCuidCookie(): ?string
    {
        return $this->cookieManager->getCookie(self::COOKIE_NAME);
    }

    public function setCuidCookie(): string
    {
        $uuid = $this->getCuidCookie();

        if (empty($uuid)) {
            $uuid = $this->generateUuid();
            $metadata = $this->createCookieMetadata();
            $this->cookieManager->setPublicCookie(self::COOKIE_NAME, $uuid, $metadata);
        }

        return $uuid;
    }

    public function deleteCuidCookie(): void
    {
        $metadata = $this->cookieMetadataFactory->createCookieMetadata()
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());
        $this->cookieManager->deleteCookie(self::COOKIE_NAME, $metadata);
    }

    private function createCookieMetadata(): PublicCookieMetadata
    {
        return $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(self::COOKIE_EXPIRATION)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());
    }

    private function generateUuid(): string
    {
        return bin2hex(random_bytes(16));
    }
}
