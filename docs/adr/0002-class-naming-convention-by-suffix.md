# 0002. Class Naming Convention By Suffix

## Status

Accepted

## Context

In a growing software system, the number of classes, responsibilities, and dependencies continuously increases. Without
clear naming conventions, inconsistency appears quickly, which makes readability, maintainability, and onboarding more
difficult.

This package is explicitly structured around interchangeable responsibilities such as controllers, factories,
repositories, DTOs, contracts, resolvers, validators, runtime adapters, Laravel service providers, commands, and
SabreDAV node types. In such an architecture, it must be possible to recognize the role of a class directly from its
name.

At the same time, class names must not repeat context that is already expressed by the namespace or directory
structure. Otherwise names become longer without adding information, and the code starts encoding the same concept in
two places.

## Decision

All classes must follow a clearly defined naming scheme in which the responsibility is indicated by a suffix.

The suffix must match the actual architectural or framework role of the class. The goal is not to force every class
into a small generic list, but to make responsibilities explicit and consistent.

The prefix, when present, must add domain meaning that is not already provided by the namespace.

### Allowed and required suffixes

| Type                  | Suffix             | Description                                |
|-----------------------|--------------------|--------------------------------------------|
| Services              | `*Service`         | Contains business logic                    |
| Repositories          | `*Repository`      | Encapsulates data access                   |
| Factory classes       | `*Factory`         | Creates objects                            |
| Interfaces            | `*Interface`       | Defines contracts                          |
| Value objects         | `*ValueObject`     | Immutable data objects                     |
| Data transfer objects | `*Dto`             | Data transport structure                   |
| Exceptions            | `*Exception`       | Error cases                                |
| Controllers           | `*Controller`      | Entry logic, for example HTTP              |
| Policies              | `*Policy`          | Authorization rules                        |
| Service providers     | `*ServiceProvider` | Laravel service-provider integration       |
| Validators            | `*Validator`       | Validation of credentials or input         |
| Resolvers             | `*Resolver`        | Runtime resolution logic                   |
| Authorizers           | `*Authorization`   | Authorization adapters / guards            |
| Authenticators        | `*Authenticator`   | Authentication orchestration               |
| Backends              | `*Backend`         | Library-facing auth/runtime backend        |
| Builders              | `*Builder`         | Incremental object/tree construction       |
| Configurators         | `*Configurator`    | Runtime configuration of collaborators     |
| Runners               | `*Runner`          | Executes a prepared runtime                |
| Extractors            | `*Extractor`       | Extracts structured values from input      |
| Registers             | `*Register`        | Registration of bindings or package parts  |
| Commands              | `*Command`         | Console command entrypoints                |
| Models                | `*Model`           | ORM / persistence-backed domain records    |
| Collections           | `*Collection`      | Collection-style aggregate or root nodes   |
| Node files            | `*File`            | File-like protocol node                    |
| Node directories      | `*Directory`       | Directory-like protocol node               |
| Event listeners       | `*Listener`        | Reacts to events                           |
| Event dispatchers     | `*Dispatcher`      | Emits events                               |

### Examples

- `UserService`
- `OrderRepository`
- `PaymentFactory`
- `LoggerInterface`
- `PathPolicy`
- `ServerServiceProvider`
- `DatabaseCredentialValidator`
- `DefaultSpaceResolver`
- `SabreServerRunner`
- `EmailDto`
- `AuthenticationException`

## Rules

1. **No deviation from the naming scheme**

   Classes without a clear role suffix are not allowed. The suffix must be chosen from the approved role list in this
   ADR.

2. **Exactly one responsibility per class**

   The name must reflect the actual role. Mixed forms such as `UserServiceRepository` are not allowed.
   Framework-specific roles must keep the suffix that matches the framework role:
   - Laravel provider: `*ServiceProvider`
   - Laravel command: `*Command`
   - SabreDAV collection or root collection: `*Collection`

3. **Interface requirement for abstracted components**

   Every abstracted service, repository, builder, resolver, runner, configurator, extractor, validator, authenticator,
   or authorization adapter should have a corresponding interface when that role is part of the package extension
   surface. Examples:
   - `UserServiceInterface` / `UserService`
   - `SpaceResolverInterface` / `DefaultSpaceResolver`

4. **No generic names**

   Forbidden: `Helper`, `Manager`, `Util`. Use a clear domain-specific name instead.

5. **Suffix is mandatory, prefix is optional**

   The domain is expressed through the prefix. Examples: `UserService`, `InvoiceRepository`.

6. **Namespace context must not be duplicated in the class name**

   A class name must not repeat domain or subsystem terms that are already clearly expressed by its namespace or
   directory. Move classes into the correct sub-namespace instead of encoding that context again in the class name.
   Prefer the shortest name that is still unambiguous inside its namespace.

   Forbidden:
   - `DTO\Auth\WebDavAccountRecordDto`
   - `DTO\Server\WebDavRequestContextDto`
   - `N3XT0R\LaravelWebdavServerFilament\WebdavServerFilamentServiceProvider`

   Preferred:
   - `DTO\Auth\AccountRecordDto`
   - `DTO\Server\RequestContextDto`
   - `LaravelWebdavServerFilamentServiceProvider` or a correctly scoped sub-namespace with `ServiceProvider`

7. **Names must not compensate for missing structure**

   If a class name needs a long package, protocol, or subsystem prefix to be understandable, that usually indicates
   that the class belongs in a more specific namespace. Prefer moving the class to a better namespace over inventing
   longer names such as `WebDav*`, `Filament*`, or `Server*` when that context is already architectural rather than
   semantic.

8. **New code is immediately normative**

   This ADR is binding for all new code from the point of acceptance.

9. **Framework and library semantics take precedence**

   A class must not be renamed into a semantically wrong suffix just to satisfy the naming list. Examples of correct
   framework-aligned names:
   - `ServiceProvider`, not `ServerService`
   - `Command`, not `ServerService`
   - `SabreServerRunner`, not `SabreServerService`

## Consequences

Advantages:

- responsibilities are visible immediately
- code readability improves
- the project structure becomes more uniform
- refactoring becomes easier
- IDEs and tooling can reason more effectively about class roles
- class names become shorter and less repetitive

Disadvantages:

- some classes may need to move into more specific namespaces before a good short name becomes available
- special cases have less naming flexibility
- the convention must be applied consistently
- the approved suffix list must evolve carefully when the architecture introduces a genuinely new role

Rejected alternatives:

- **No fixed convention** — rejected because it leads to inconsistency and higher maintenance cost.
- **Annotations instead of naming conventions** — rejected because the role of a class is less visible in the code
  itself.
- **Allow redundant prefixes for package or subsystem context** — rejected because namespaces already provide that
  context and repeating it in class names creates avoidable noise.
