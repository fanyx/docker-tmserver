FROM fanyx/php:5.6

RUN apt update \
	&& useradd -M --uid 1000 trackmania \
	&& mkdir /opt/tmserver /opt/xaseco \
	&& chown -R trackmania:trackmania /opt/tmserver \
	&& chown -R trackmania:trackmania /opt/xaseco

USER trackmania
COPY /tmserver:/opt/tmserver
COPY /xaseco:/opt/xaseco

WORKDIR /opt/tmserver

ENTRYPOINT ["./tmserver"]

