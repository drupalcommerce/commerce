<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\profile\Entity\ProfileType;
use Drupal\profile\Event\ProfileLabelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProfileLabelSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'profile.label' => 'onLabel',
    ];
    return $events;
  }

  /**
   * Sets the customer profile label to the first address line.
   *
   * This behavior is restricted to customer profile types.
   *
   * @param \Drupal\profile\Event\ProfileLabelEvent $event
   *   The profile label event.
   */
  public function onLabel(ProfileLabelEvent $event) {
    /** @var \Drupal\profile\Entity\ProfileInterface $order */
    $profile = $event->getProfile();
    $profile_type = ProfileType::load($profile->bundle());
    $customer_flag = $profile_type->getThirdPartySetting('commerce_order', 'customer_profile_type');
    if ($customer_flag && $profile->hasField('address') && !$profile->get('address')->isEmpty()) {
      $event->setLabel($profile->get('address')->address_line1);
    }
  }

}
