FROM fanyx/php:5.6

COPY /tmserver:/opt/tmserver
COPY /xaseco:/opt/xaseco

RUN apt update \
	&& groupadd trackmania \
	&& useradd -M -g trackmania trackmania \
	&& chown -R trackmania:trackmania /opt/tmserver \
	&& chown -R trackmania:trackmania /opt/xaseco

USER trackmania
WORKDIR /opt/tmserver

ENTRYPOINT ["./tmserver"]
CMD ["start", "tmserver"]
