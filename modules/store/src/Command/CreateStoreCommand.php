<?php

namespace Drupal\commerce_store\Command;

// @codingStandardsIgnoreStart
use CommerceGuys\Intl\Currency\CurrencyRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\commerce_price\CurrencyImporter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\address\Repository\CountryRepository;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\Console\Question\Question;
// @codingStandardsIgnoreEnd

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

  use CommandTrait;

  /**
   * The currency importer.
   *
   * @var \Drupal\commerce_price\CurrencyImporter
   */
  protected $currencyImporter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The country repository.
   *
   * @var \Drupal\address\Repository\CountryRepository
   */
  protected $countryRepository;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new CreateStoreCommand object.
   *
   * @param \Drupal\commerce_price\CurrencyImporter $commerce_price_currency_importer
   *   The currency importer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\address\Repository\CountryRepository $address_country_repository
   *   The country repository.
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   The URL generator.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(CurrencyImporter $commerce_price_currency_importer, EntityTypeManagerInterface $entity_type_manager, CountryRepository $address_country_repository, MetadataBubblingUrlGenerator $url_generator, EmailValidator $email_validator) {
    $this->currencyImporter = $commerce_price_currency_importer;
    $this->entityTypeManager = $entity_type_manager;
    $this->countryRepository = $address_country_repository;
    $this->urlGenerator = $url_generator;
    $this->emailValidator = $email_validator;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('commerce:create:store')
      ->setDescription($this->trans('commands.commerce.create.store.description'))
      ->addOption('name', '', InputOption::VALUE_REQUIRED, $this->trans('commands.commerce.create.store.options.name'))
      ->addOption('mail', '', InputOption::VALUE_REQUIRED, $this->trans('commands.commerce.create.store.options.mail'))
      ->addOption('country', '', InputOption::VALUE_REQUIRED, $this->trans('commands.commerce.create.store.options.country'))
      ->addOption('currency', '', InputOption::VALUE_OPTIONAL, $this->trans('commands.commerce.create.store.options.currency'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $country_code = $input->getOption('country');
    $currency_code = $input->getOption('currency');
    // Allow the caller to specify the country, but not the currency.
    if (empty($currency_code)) {
      $country = $this->countryRepository->get($country_code, 'en');
      $currency_code = $country->getCurrencyCode();
      if (empty($currency_code)) {
        $message = sprintf('Default currency not known for country %s, please specify one via --currency.', $country_code);
        throw new \RuntimeException($message);
      }
    }

    $store_storage = $this->entityTypeManager->getStorage('commerce_store');
    $this->currencyImporter->import($currency_code);
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

    $link = $this->urlGenerator->generate('entity.commerce_store.edit_form', ['commerce_store' => $store->id()], TRUE);
    $io->writeln(sprintf('The store has been created. Go to %s to complete the store address and manage other settings.', $link));
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
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
      $question->setValidator(function ($mail) {
        if (empty($mail) || !$this->emailValidator->isValid($mail)) {
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
      $country_names = array_flip($this->countryRepository->getList('en'));
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
      $country = $this->countryRepository->get($country, 'en');
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
