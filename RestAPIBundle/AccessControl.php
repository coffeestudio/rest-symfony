<?php
namespace CoffeeStudio\RestAPIBundle;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;


trait AccessControl
{
    protected function restricted($accessor)
    {
        if (! $accessor) $this->e403();
    }

    private function createAccessDeniedException($message)
    {
        return new AccessDeniedException($message);
    }
    private function e403($marker=null)
    {
        $marker = $marker ? ', "marker": "'.$marker.'"' : '';
        throw $this->createAccessDeniedException('{"type": "error", "message": "You have no access to this API method."'.$marker.'}');
    }
}