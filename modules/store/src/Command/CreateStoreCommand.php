<?php

/**
 * @file
 * Contains Drupal\commerce_store\Command\CreateStoreCommand.
 */

namespace Drupal\commerce_store\Command;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Drupal\Console\Command\ContainerAwareCommand;

class CreateStoreCommand extends ContainerAwareCommand {

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
    $container = $this->getContainer();
    $currency_importer = $container->get('commerce_price.currency_importer');
    $store_storage = $container->get('entity_type.manager')->getStorage('commerce_store');

    $country_code = $input->getOption('country');
    $currency_code = $input->getOption('currency');
    // Allow the caller to specify the country, but not the currency.
    if (empty($currency_code)) {
      $country_repository = $container->get('address.country_repository');
      $country = $country_repository->get($country_code, 'en');
      $currency_code = $country->getCurrencyCode();
      if (empty($currency_code)) {
        $message = sprintf('Default currency not known for country %s, please specify one via --currency.', $country_code);
        throw new \RuntimeException($message);
      }
    }

    $currency_importer->import($currency_code);
    $values = [
      'type' => 'default',
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

    $link = $container->get('url_generator')->generate('entity.commerce_store.edit_form', ['commerce_store' => $store->id()], TRUE);
    $output->writeln(sprintf('The store has been created. Go to %s to complete the store address and manage other settings.', $link));
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $container = $this->getContainer();
    $mail_validator = $container->get('email.validator');
    $country_repository = $container->get('address.country_repository');
    $currency_repository = new CurrencyRepository();
    $helper = $this->getHelper('question');
    // Symfony Console has no built-in way to ensure the value is not empty.
    $required_validator = function ($value) {
      if (empty($value)) {
        throw new \RuntimeException("Value can't be empty.");
      }
      return $value;
    };

    // --name option.
    $name = $input->getOption('name');
    if (!$name) {
      $question = new Question('Enter the store name: ', '');
      $question->setValidator($required_validator);
      $name = $helper->ask($input, $output, $question);
    }
    $input->setOption('name', $name);
    // --mail option.
    $mail = $input->getOption('mail');
    if (!$mail) {
      $question = new Question('Enter the store email: ', '');
      $question->setValidator(function ($mail) use ($mail_validator) {
        if (empty($mail) || !$mail_validator->isValid($mail)) {
          throw new \RuntimeException('The entered email is not valid.');
        }
        return $mail;
      });
      $mail = $helper->ask($input, $output, $question);
    }
    $input->setOption('mail', $mail);
    // --country option.
    $country = $input->getOption('country');
    if (!$country) {
      $country_names = array_flip($country_repository->getList('en'));
      $question = new Question('Enter the store country: ', '');
      $question->setAutocompleterValues($country_names);
      $question->setValidator($required_validator);
      $country = $helper->ask($input, $output, $question);
      $country = $country_names[$country];
    }
    $input->setOption('country', $country);
    // --currency option.
    $currency = $input->getOption('currency');
    if (!$currency) {
      $country = $country_repository->get($country, 'en');
      $currency_code = $country->getCurrencyCode();
      if ($currency_code) {
        $question = new Question("Enter the store currency [$currency_code]: ", $currency_code);
      }
      else {
        $question = new Question('Enter the store currency: ');
      }
      $question->setAutocompleterValues(array_keys($currency_repository->getList('en')));
      $question->setValidator($required_validator);
      $currency = $helper->ask($input, $output, $question);
    }
    $input->setOption('currency', $currency);
  }

}
