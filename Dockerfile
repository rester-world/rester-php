FROM hamiltont/docker-cron
MAINTAINER Kevin Park<kevinpark@webace.co.kr>

RUN apt-get update && apt-get install curl -y
RUN curl -sL https://deb.nodesource.com/setup_8.x | bash -
RUN apt-get update && apt-get install nodejs -y

RUN mkdir /scripts

VOLUME ["/cron"]
VOLUME ["/scripts"]

