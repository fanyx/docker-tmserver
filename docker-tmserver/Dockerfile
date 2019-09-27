FROM debian:buster-slim 

COPY tmserver/ /opt/tmserver
COPY entrypoint-tmserver.sh /opt/tmserver/

RUN apt update \
	&& apt install -y pwgen gettext-base

RUN groupadd trackmania
RUN useradd -M -g trackmania trackmania
RUN chown -R trackmania:trackmania /opt/tmserver

USER trackmania

EXPOSE 5000

CMD ["/opt/tmserver/entrypoint-tmserver.sh"]
