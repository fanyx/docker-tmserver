FROM fanyx/php:5.6

RUN mkdir /opt/tmserver 

COPY tmserver/ /opt/tmserver/
COPY ./entrypoint-tmserver.sh /

RUN apt update \
	&& apt install pwgen
RUN groupadd trackmania
RUN useradd -M -g trackmania trackmania
RUN chown -R trackmania:trackmania /opt/tmserver
RUN chown trackmania:trackmania /entrypoint-tmserver.sh

USER trackmania
WORKDIR /opt/tmserver
CMD ["/entrypoint-tmserver.sh"]
