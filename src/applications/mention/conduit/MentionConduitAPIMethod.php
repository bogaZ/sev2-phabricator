<?php

abstract class MentionConduitAPIMethod extends ConduitAPIMethod {
    protected function setResponseMessage($message, bool $error) {
    return array(
      'message' => $message,
      'error' => $error,
    );
  }
}
