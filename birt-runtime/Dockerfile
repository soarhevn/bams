FROM tomcat:9.0.2-jre8-alpine

ENV BIRT_VIEWER_HOME=$CATALINA_HOME/webapps/birt

# Copy over the needed BIRT files
COPY WebViewerExample ${BIRT_VIEWER_HOME}
COPY BAMS_Docker-ConnectionProfile ${BIRT_VIEWER_HOME}

# Install JDBC MySQL/MariaDB Driver
# COPY mariadb-java-client* ${BIRT_VIEWER_HOME}/WEB-INF/lib/
COPY mysql-connector-java* ${BIRT_VIEWER_HOME}/WEB-INF/lib/

# Configure
COPY tomcat-users.xml $CATALINA_HOME/conf/

# since tomcat 8.5+ disallow access manager from anywhere but localhost
RUN mkdir -p $CATALINA_HOME/conf/Catalina/localhost \
  && echo '<Context privileged="true" antiResourceLocking="false" docBase="${catalina.home}/webapps/manager"><Valve className="org.apache.catalina.valves.RemoteAddrValve" allow="^.*$" /></Context>' | tee $CATALINA_HOME/conf/Catalina/localhost/manager.xml

CMD ["catalina.sh", "run"]
