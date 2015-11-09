<?php

/**
 * @file
 * Contains Drupal\commerce_store\Command\CreateStoreCommand.
 */

namespace Drupal\commerce_store\Command;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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
    $currencyImporter = $container->get('commerce_price.currency_importer');
    $storeStorage = $container->get('entity_type.manager')->getStorage('commerce_store');

    $countryCode = $input->getOption('country');
    $currencyCode = $input->getOption('currency');
    // Allow the caller to specify the country, but not the currency.
    if (empty($currencyCode)) {
      $countryRepository = $container->get('address.country_repository');
      $country = $countryRepository->get($countryCode, 'en');
      $currencyCode = $country->getCurrencyCode();
      if (empty($currencyCode)) {
        $message = sprintf('Default currency not known for country %s, please specify one via --currency.', $countryCode);
        throw new \RuntimeException($message);
      }
    }

    $currency = $currencyImporter->import($currencyCode);
    $values = [
      'type' => 'default',
      'uid' => 1,
      'name' => $input->getOption('name'),
      'mail' => $input->getOption('mail'),
      'address' => [
        'country_code' => $countryCode,
      ],
      'default_currency' => $currencyCode,
    ];
    $store = $storeStorage->create($values);
    $store->save();
    // Make this the default store, since there's no other.
    if (!$storeStorage->loadDefault()) {
      $storeStorage->markAsDefault($store);
    }

    $link = $container->get('url_generator')->generate('entity.commerce_store.edit_form', ['commerce_store' => $store->id()], TRUE);
    $output->writeln(sprintf('The store has been created. Go to %s to complete the store address and manage other settings.', $link));
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $container = $this->getContainer();
    $emailValidator = $container->get('email.validator');
    $countryRepository = $container->get('address.country_repository');
    $currencyRepository = new CurrencyRepository();
    $helper = $this->getHelper('question');
    // Symfony Console has no built-in way to ensure the value is not empty.
    $requiredValidator = function ($value) {
      if (empty($value)) {
        throw new \RuntimeException("Value can't be empty.");
      }
      return $value;
    };

    // --name option.
    $name = $input->getOption('name');
    if (!$name) {
      $question = new Question('Enter the store name: ', '');
      $question->setValidator($requiredValidator);
      $name = $helper->ask($input, $output, $question);
    }
    $input->setOption('name', $name);
    // --mail option.
    $mail = $input->getOption('mail');
    if (!$mail) {
      $question = new Question('Enter the store email: ', '');
      $question->setValidator(function ($mail) use ($emailValidator) {
        if (empty($mail) || !$emailValidator->isValid($mail)) {
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
      $countryNames = array_flip($countryRepository->getList('en'));
      $question = new Question('Enter the store country: ', '');
      $question->setAutocompleterValues($countryNames);
      $question->setValidator($requiredValidator);
      $country = $helper->ask($input, $output, $question);
      $country = $countryNames[$country];
    }
    $input->setOption('country', $country);
    // --currency option.
    $currency = $input->getOption('currency');
    if (!$currency) {
      $country = $countryRepository->get($country, 'en');
      $currencyCode = $country->getCurrencyCode();
      if ($currencyCode) {
        $question = new Question("Enter the store currency [$currencyCode]: ", $currencyCode);
      }
      else {
        $question = new Question('Enter the store currency: ');
      }
      $question->setAutocompleterValues(array_keys($currencyRepository->getList('en')));
      $question->setValidator($requiredValidator);
      $currency = $helper->ask($input, $output, $question);
    }
    $input->setOption('currency', $currency);
  }

}
