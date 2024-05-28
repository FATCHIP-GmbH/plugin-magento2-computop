<?php

namespace Fatchip\Computop\Plugin;

/**
 * Needed to preserve session cookie when redirected back from computop with POST requests.
 */
class RedirectSessionChecker
{
    /**
     * @var string[]
     */
    protected $whitelistReturnUrls = [
        'computop/onepage/ccReturn',
    ];

    protected $request;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(\Magento\Framework\App\Request\Http $request)
    {
        $this->request = $request;
    }

    /**
     * Prevents session starting when returning from a redirect to Computop with a POST request
     *
     * @param \Magento\Framework\Session\SessionStartChecker $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCheck(\Magento\Framework\Session\SessionStartChecker $subject, bool $result): bool
    {
        if ($result === false) {
            return false;
        }

        foreach ($this->whitelistReturnUrls as $url) {
            if (strpos((string)$this->request->getPathInfo(), $url) !== false) {
                return false;
            }
        }

        return true;
    }
}
