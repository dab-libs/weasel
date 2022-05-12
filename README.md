<div align="right"><a href="README_ru.md">Русская версия</a></div>

# Weasel

Weasel - is a small library designed to simplify integration testing of [Symfony](https://symfony.com/) applications.

## Installation

Install via [Composer](https://getcomposer.org/):

```shell
$ composer require --dev dab-libs/waesel-bundle
```

## Usage

Suppose we want to test a pet search service by name or ID. This service implements the interface:

```php
interface FindPets {
  /** @return Pet[] */
  public function do(?string $id, ?string $name): array;
}
```

It finds pets by ID, by name, or both. It returns an array of found pets, or an empty array.

To test the FindPets service, we must first create the initial state of the database. To do this, we create a fixture
class by implementing the Fixture interface:

```php
class FindPets_Fixture implements Fixture {
  const PET_1 = 'pet1';
  const PET_2 = 'pet2';

  public Pet $pet1;
  public Pet $pet1_2;
  public Pet $pet2;

  public function __construct(
    private CreatePet $createPet,
  ) {
  }

  public function createData(): void {
    $this->pet1 = $this->createPet->do(self::PET_1, Pet::CAT);
    $this->pet1_2 = $this->createPet->do(self::PET_1, Pet::DOG);
    $this->pet2 = $this->createPet->do(self::PET_2, Pet::CAT);
  }
}
```

The createData method of the Fixture interface is just designed to create the initial state of the database. It will be
called automatically before running the test.

Let's make the fixture class a public service:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: true

  Weasel\TestBench\Tests\UseCase\Pet\FindPets\FindPets_Fixture:
```

Now let's create a test case class by inheriting it from the DbTestCase class from the Weasel library:

```php
class FindPets_Test extends DbTestCase {
  /** @RequiredForTest) */
  private ?FindPets $findPets = null;
  /** @RequiredForTest) */
  private ?FindPets_Fixture $fixture = null;

  public function testFindTwoByName() {
    $pets = $this->findPets->do(null, $this->fixture::PET_1);
    self::assertTrue(in_array($this->fixture->pet1, $pets));
    self::assertTrue(in_array($this->fixture->pet1_2, $pets));
    self::assertFalse(in_array($this->fixture->pet2, $pets));
  }

  public function testFindOneByName() {
    $pets = $this->findPets->do(null, $this->fixture::PET_2);
    self::assertCount(1, $pets);
    self::assertEquals($this->fixture->pet2, $pets[0]);
  }

  public function testFindOneById() {
    $pets = $this->findPets->do($this->fixture->pet1->getId(), null);
    self::assertCount(1, $pets);
    self::assertEquals($this->fixture->pet1, $pets[0]);
  }
}
```

Next we describe fields for the FindPets service and fixtures in this class. We mark them with the @RequiredForTest
annotation. Now, the FindPets service and the fixture will be automatically requested from DI container and assigned to
the appropriate fields before running a test. It will be done in the setUp method of the base class. Then the method
createData of the fixture class will be called. And after that, the test method will be executed, and we can use the
injected services.

## Why Weasel

### Symfony services in tests without Weasel

Almost all functionality of Symfony based applications is implemented as services. When testing, these services must be
obtained from the DI container. Symfony developers advise doing it
like [this](https://symfony.com/doc/current/testing.html#integration-tests):

1. initialize the Symfony kernel,
2. get the DI container using the getContainer method of the KernelTestCase class,
3. get the necessary services from the DI container.

```php 
class NewsletterGeneratorTest extends KernelTestCase {
    public function testSomething() {
        self::bootKernel();
        $container = static::getContainer();

        $newsletterGenerator = $container->get(NewsletterGenerator::class);
        $newsletter = $newsletterGenerator->generateMonthlyNews(...);

        $this->assertEquals('...', $newsletter->getContent());
    }
}
```

### Symfony services in tests using Weasel

Getting a service directly from a DI container is not a natural practice for the average Symfony programmer. We get
services by injecting them through constructor parameters or through setters.

The Weasel library allows us to get services by simply describing them as fields in the test case class and annotating
them with the @RequiredForTest annotation:

```php
class FindPets_Test extends DbTestCase {
  /** @RequiredForTest) */
  private ?FindPets $findPets = null;
  /** @RequiredForTest) */
  private ?FindPets_Fixture $fixture = null;

  public function testFindTwoByName() {
    ...
  }
  
  ...
}
```

This saves the programmer from having to explicitly access the DI container and makes working with services simple and
familiar. Now a programmer can focus on writing tests without being distracted by writing the same type of code to get
services from the DI container.

## Weasel classes

The Weasel library provides several base classes for writing integration and functional tests:

* KernelTestCase - for integration tests without using a database
* DbTestCase - for integration tests using a database
* WebTestCase - for functional tests without using a database
* WebDbTestCase - for functional tests using a database
