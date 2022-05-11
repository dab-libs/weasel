# Библиотека Weasel

Weasel - это небольшая библиотека, предназначенная для упрощения интеграционного тестирования приложений на основе
[Symfony](https://symfony.com/).

## Установка

Установите, используя [Composer](https://getcomposer.org/):

```shell
$ composer require --dev dab-libs/waesel-bundle
```

## Использование

Пусть необходимо протестировать сервис поиска петов по имени или идентификатору. Этот сервис реализует следующий
интерфейс:

```php
interface FindPets {
  /** @return Pet[] */
  public function do(?string $id, ?string $name): array;
}
```

Он ищет петов по идентификатору, или по имени, или и по тому, и по другому. Он возвращает массив найденных петов, или
пустой массив.

Чтобы протестировать сервис FindPets, необходимо сначала заполнить базу данных. Для этого создаём класс фикстуры,
реализовав интерфейс Fixture.

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

Метод createData интерфейса Fixture как раз предназначен для создания начального состояния базы данных. Он будет вызван
автоматически перед запуском теста.

Опишем класс фикстуры как сервис, сделав его публичным:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: true

  Weasel\TestBench\Tests\UseCase\Pet\FindPets\FindPets_Fixture:
```

Теперь создадим тестовый класс, унаследовав его от класса DbTestCase из библиотеки Weasel:

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

Опишем в этом классе поля для сервиса FindPets и фикстуры. Пометим их аннотацией @RequiredForTest. Теперь при запуске
теста в базовом методе setUp сервис FindPets и фикстура будут автоматически запрошены из контейнера зависимостей и
присвоены соответствующим полям тестового класса. Затем у сервиса фикстуры будет вызван метод createData. И уже после
этого будет выполнен тестовый метод, в котором можно свободно использовать сервисы, внедренные в тестовый контекст.

## Для чего необходима библиотека Weasel

### Интеграционные тесты вместо модульных тестов

Для большинства веб-приложений (в том числе, и основанных на Symfony) модульные тесты бесполезны. Это связанно с тем,
что приложения на основе Symfony состоят из множества сервисов. Каждый из них реализует очень простые алгоритмы, но при
этом зависит от большого количества других сервисов. Из-за этого модульные тесты зависят от большого количество
мок-объектов. К сожалению, такие тесты малоэффективны или очень хрупки.

С другой стороны, в этой ситуации интеграционные тесты позволяют протестировать процесс обработки каждого запроса. Они
помогают проверить не только алгоритмы обработки данных, но и описание сервисов, используемых в обработке запроса, и их
взаимодействие. Модульные тесты не позволяют выявить такие ошибки. Ошибки, связанные с загрузкой/выгрузкой данных из/в
базу данных, тоже не поддаётся тестированию с помощью модульных тестов, но легко выявляются при интеграционном
тестировании. При этом интеграционные тесты ломаются лишь при изменении формата или семантики входных и выходных данных.
А это случается достаточно редко.

### Сервисы Symfony в тестах без Weasel

Практически весь функционал приложений на основе Symfony реализован в виде сервисов. При тестировании эти сервисы
необходимо получить из DI-контейнера. Разработчики Symfony советуют делать это
[следующим образом](https://symfony.com/doc/current/testing.html#integration-tests):

1. инициализировать ядро Symfony,
2. получить DI-контейнер с помощью метода getContainer класса KernelTestCase,
3. получить из DI-контейнера необходимые для теста сервисы.

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

### Сервисы Symfony в тестах с использованием Weasel

Для обычного Symfony-программиста получение сервиса напрямую из контейнера зависимостей является неестественной
практикой. Мы привыкли получать сервисы с помощью внедрения их через параметры конструктора или через сетеры.

Библиотека Weasel позволяет описывать необходимые для теста сервисы в виде полей в тестовом классе, пометив их
аннотацией @RequiredForTest:

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

Это даёт возможность избавить программиста от явного обращения к контейнеру зависимостей, и получать все
необходимые для теста сервисы просто описывая поля тестового класса. Таким образом, программист может сосредоточиться 
на написании тестов, не отвлекаясь на написание однотипного кода для получения сервисов из контейнера зависимостей.

## Классы Weasel

Библиотека Weasel предоставляет несколько базовых классов для написания интеграционных и функциональных тестов:

* KernelTestCase - для интеграционных тестов без использования базы данных
* DbTestCase - для интеграционных тестов с использованием базы данных
* WebTestCase - для функциональных тестов без использования базы данных
* WebDbTestCase - для с тестов с использованием базы данных
