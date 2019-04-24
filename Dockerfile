FROM rester/rester-docker
MAINTAINER Kevin Park<kevinpark@webace.co.kr>

RUN mkdir /var/www/cfg
RUN mkdir /var/www/src
RUN mkdir /var/www/lib
RUN mkdir /var/www/exten_lib

ADD cfg /var/www/cfg
ADD src /var/www/src
ADD lib /var/www/lib
ADD default.conf /etc/nginx/sites-available/default.conf
ADD default-ssl.conf /etc/nginx/sites-available/default-ssl.conf

VOLUME ["/var/www/cfg"]
VOLUME ["/var/www/src"]
VOLUME ["/var/www/lib"]
VOLUME ["/var/www/exten_lib"]

