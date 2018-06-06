<?php

namespace TheAentMachine\AentMysql\Command;

class Cst
{
    // intercepts these events
    public const ADD_EVENT= "ADD";
    public const REMOVE_EVENT = "REMOVE";

    // sends these events
    public const NEW_DOCKER_SERVICE_INFO_EVENT= "NEW-DOCKER-SERVICE-INFO";
    public const DELETE_DOCKER_SERVICE_EVENT= "DELETE-DOCKER-SERVICE";

    // payload
    public const SERVICE_NAME_KEY = "serviceName";
    public const NAMED_VOLUMES_KEY = "namedVolumes";

    // aenthill
    public const AENTHILL_JSON_PATH = "/aenthill/aenthill.json";

    // default aent images
    public const DEFAULT_DOCKER_COMPOSE_IMG = "theaentmachine/aent-docker-compose";
    public const DEFAULT_KUBERNETES_IMG = "theaentmachine/aent-kubernetes";
}
