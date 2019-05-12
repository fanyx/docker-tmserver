FROM fanyx/php

RUN mkdir /opt/xaseco

COPY xaseco/ /opt/xaseco/
COPY ./entrypoint-xaseco.sh /

RUN apt update -y \ 
  && apt upgrade -y \
RUN groupadd trackmania
RUN useradd -M -g trackmania trackmania
RUN chown -R trackmania:trackmania /opt/xaseco
RUN chown trackmania:trackmania /entrypoint-xaseco.sh

USER trackmania
WORKDIR /opt/xaseco
CMD ["/entrypoint-xaseco.sh"]
