<?php
/**
 * Created by PhpStorm.
 * User: monakuen
 * Date: 2/02/18
 * Time: 19:57
 */

namespace SP\ShareBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegistrationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::RESETTING_RESET_SUCCESS =>'onRegistrationSuccess'
        );
    }

    public function onRegistrationSuccess(FormEvent $event){
        $roles = array('ROLE_BIDON');

        $user = $event->getForm()->getData();
        $user->setRoles($roles);
    }
}