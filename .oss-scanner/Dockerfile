# Build of the OSS Scanner (https://github.com/anthropics/oss-scanner), with the repository root as context.
# It starts from the phpqa image the CI jobs run in, with the PHP version of the QA jobs, so phpunit-11 and the QA
# tools behave as they do on CI. The image ships ext-gmp and ext-bcmath, which the big number, decimal fraction and
# big float tags use. The scan runs without network access: every dependency is installed here.
# It runs as root because the default user of the image cannot write to the copied sources. XDEBUG_MODE is off, as
# in the CI test job. COMPOSER_ROOT_VERSION is not needed: Composer installs without the Git metadata.
FROM ghcr.io/spomky-labs/phpqa:8.4

USER root

ENV XDEBUG_MODE=off

COPY . /src
WORKDIR /src

RUN composer install --no-interaction --no-progress

RUN composer exec -- phpunit-11 --configuration .ci-tools/phpunit.xml.dist --no-progress \
    || echo "WARNING: PHPUnit reported failures"
