FROM php:5.6-alpine
ARG S6_OVERLAY_VERSION=3.1.1.1

RUN apk add --no-cache pwgen gettext xmlstarlet bash xz
RUN docker-php-ext-install mysql

COPY tmserver/ /var/lib/tmserver
COPY xaseco/ /var/lib/xaseco

RUN addgroup -S trackmania && adduser -D -H -S trackmania -G trackmania
RUN chown -R trackmania:trackmania /var/lib/tmserver /var/lib/xaseco

EXPOSE 5000

CMD ["/var/lib/tmserver/entrypoint.sh"]

ADD https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-noarch.tar.xz /tmp
RUN tar -C / -Jxpf /tmp/s6-overlay-noarch.tar.xz
ADD https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-x86_64.tar.xz /tmp
RUN tar -C / -Jxpf /tmp/s6-overlay-x86_64.tar.xz
ENTRYPOINT ["/init"]

RUN touch /etc/s6-overlay/s6-rc.d/user/contents.d/xaseco
COPY services.d/xaseco/ /etc/s6-overlay/s6-rc.d/xaseco/
