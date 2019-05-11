FROM fanyx/php:5.6

RUN mkdir /opt/tmserver /opt/xaseco

COPY tmserver/ /opt/tmserver/
COPY xaseco/ /opt/xaseco/
COPY ./entrypoint.sh /

RUN apt update \
	&& apt install pwgen
RUN groupadd trackmania
RUN useradd -M -g trackmania trackmania
RUN chown -R trackmania:trackmania /opt/tmserver
RUN chown -R trackmania:trackmania /opt/xaseco
RUN chown trackmania:trackmania /entrypoint.sh

USER trackmania
WORKDIR /opt/tmserver
CMD ["bash"]
