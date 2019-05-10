FROM fanyx/php:5.6

ENV TMDIR=/tmserver

RUN apt update \
	&& useradd -M --uid 1000 trackmania \
	&& mkdir $TMDIR \
	&& chown -R trackmania:trackmania $TMDIR

EXPOSE 2351 2351/udp 3451 3451/udp 5001 5001/udp

USER trackmania
VOLUME /tmserver:/tmserver
WORKDIR /tmserver
ENTRYPOINT
