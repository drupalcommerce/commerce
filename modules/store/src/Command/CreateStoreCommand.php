<?php

namespace Drupal\commerce_store\Command;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class CreateStoreCommand.
 *
 * @package Drupal\commerce_store
 *
 * @DrupalCommand (
 *     extension="commerce_store",
 *     extensionType="module"
 * )
 */
class CreateStoreCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('commerce:create:store')
      ->setDescription('Create a new store')
      ->addOption('name', '', InputOption::VALUE_REQUIRED, 'The store name')
      ->addOption('mail', '', InputOption::VALUE_REQUIRED, 'The store email')
      ->addOption('country', '', InputOption::VALUE_REQUIRED, 'The store country')
      ->addOption('currency', '', InputOption::VALUE_OPTIONAL, 'The store currency (optional)');
  }


  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $currency_importer = $this->get('commerce_price.currency_importer');
    /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
    $store_storage = $this->get('entity_type.manager')->getStorage('commerce_store');
    $country_code = $input->getOption('country');
    $currency_code = $input->getOption('currency');
    // Allow the caller to specify the country, but not the currency.
    if (empty($currency_code)) {
      /** @var \CommerceGuys\Intl\Country\CountryRepositoryInterface $country_repository */
      $country_repository = $this->get('address.country_repository');
      $country = $country_repository->get($country_code, 'en');
      $currency_code = $country->getCurrencyCode();
      if (empty($currency_code)) {
        $message = sprintf('Default currency not known for country %s, please specify one via --currency.', $country_code);
        throw new \RuntimeException($message);
      }
    }

    $currency_importer->import($currency_code);
    $values = [
      'type' => 'online',
      'uid' => 1,
      'name' => $input->getOption('name'),
      'mail' => $input->getOption('mail'),
      'address' => [
        'country_code' => $country_code,
      ],
      'default_currency' => $currency_code,
    ];
    $store = $store_storage->create($values);
    $store->save();
    // Make this the default store, since there's no other.
    if (!$store_storage->loadDefault()) {
      $store_storage->markAsDefault($store);
    }

    $link = $this->get('url_generator')->generate('entity.commerce_store.edit_form', ['commerce_store' => $store->id()], TRUE);
    $output->writeln(sprintf('The store has been created. Go to %s to complete the store address and manage other settings.', $link));
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $mail_validator = $this->get('email.validator');
    $country_repository = $this->get('address.country_repository');
    $currency_repository = new CurrencyRepository();

    // --name option.
    $name = $input->getOption('name');
    if (!$name) {
      $name = $io->ask('Enter the store name', '');
    }
    $input->setOption('name', $name);
    // --mail option.
    $mail = $input->getOption('mail');
    if (!$mail) {
      $mail = $io->ask('Enter the store email', '', function ($mail) use ($mail_validator) {
        if (empty($mail) || !$mail_validator->isValid($mail)) {
          throw new \RuntimeException('The entered email is not valid.');
        }
        return $mail;
      });
    }
    $input->setOption('mail', $mail);
    // --country option.
    $country = $input->getOption('country');
    if (!$country) {
      $country_names = array_flip($country_repository->getList('en'));
      $country = $io->choiceNoList('Enter the store country', $country_names, '');
      $country = $country_names[$country];
    }
    $input->setOption('country', $country);
    // --currency option.
    $currency = $input->getOption('currency');
    if (!$currency) {
      $country = $country_repository->get($country, 'en');
      $currency_code = $country->getCurrencyCode();
      $currency_names = array_keys($currency_repository->getList('en'));
      $currency = $io->choiceNoList('Enter the store currency', $currency_names, $currency_code);
    }
    $input->setOption('currency', $currency);
  }
}
