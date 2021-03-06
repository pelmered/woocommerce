<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Provides logging capabilities for debugging purposes.
 *
 * @class          WC_Logger
 * @version        2.0.0
 * @package        WooCommerce/Classes
 * @category       Class
 * @author         WooThemes
 */
class WC_Logger implements WC_Logger_Interface {

	/**
	 * Stores registered log handlers.
	 *
	 * @var array
	 */
	protected $handlers;

	/**
	 * Minimum log level this handler will process.
	 *
	 * @var int Integer representation of minimum log level to handle.
	 */
	protected $threshold;

	/**
	 * Constructor for the logger.
	 *
	 * @param array $handlers Optional. Array of log handlers. If $handlers is not provided,
	 *     the filter 'woocommerce_register_log_handlers' will be used to define the handlers.
	 *     If $handlers is provided, the filter will not be applied and the handlers will be
	 *     used directly.
	 * @param string $threshold Optional. Define an explicit threshold. May be configured
	 *     via  WC_LOG_THRESHOLD. By default, all logs will be processed.
	 */
	public function __construct( $handlers = null, $threshold = null ) {
		if ( null === $handlers ) {
			$handlers = apply_filters( 'woocommerce_register_log_handlers', array() );
		}

		if ( null !== $threshold ) {
			$threshold = WC_Log_Levels::get_level_severity( $threshold );
		} elseif ( defined( 'WC_LOG_THRESHOLD' ) && WC_Log_Levels::is_valid_level( WC_LOG_THRESHOLD ) ) {
			$threshold = WC_Log_Levels::get_level_severity( WC_LOG_THRESHOLD );
		} else {
			$threshold = null;
		}

		$this->handlers  = $handlers;
		$this->threshold = $threshold;
	}

	/**
	 * Determine whether to handle or ignore log.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return bool True if the log should be handled.
	 */
	public function should_handle( $level ) {
		if ( null === $this->threshold ) {
			return true;
		}
		return $this->threshold <= WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * Add a log entry.
	 *
	 * This is not the preferred method for adding log messages. Please use log() or any one of
	 * the level methods (debug(), info(), etc.). This method may be deprecated in the future.
	 *
	 * @param string $handle
	 * @param string $message
	 * @return bool
	 */
	public function add( $handle, $message, $level = WC_Log_Levels::NOTICE ) {
		$message = apply_filters( 'woocommerce_logger_add_message', $message, $handle );
		$this->log( $level, $message, array( 'source' => $handle, '_legacy' => true ) );
		wc_do_deprecated_action( 'woocommerce_log_add', array( $handle, $message ), '2.7', 'This action has been deprecated with no alternative.' );
		return true;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $level One of the following:
	 *     'emergency': System is unusable.
	 *     'alert': Action must be taken immediately.
	 *     'critical': Critical conditions.
	 *     'error': Error conditions.
	 *     'warning': Warning conditions.
	 *     'notice': Normal but significant condition.
	 *     'informational': Informational messages.
	 *     'debug': Debug-level messages.
	 * @param string $message Log message.
	 * @param array $context Optional. Additional information for log handlers.
	 */
	public function log( $level, $message, $context = array() ) {
		if ( ! WC_Log_Levels::is_valid_level( $level ) ) {
			$class = __CLASS__;
			$method = __FUNCTION__;
			wc_doing_it_wrong( "{$class}::{$method}", sprintf( __( 'WC_Logger::log was called with an invalid level "%s".', 'woocommerce' ), $level ), '2.7' );
		}

		if ( $this->should_handle( $level ) ) {
			$timestamp = current_time( 'timestamp' );

			foreach ( $this->handlers as $handler ) {
				$handler->handle( $timestamp, $level, $message, $context );
			}
		}
	}

	/**
	 * Adds an emergency level message.
	 *
	 * System is unusable.
	 *
	 * @see WC_Logger::log
	 */
	public function emergency( $message, $context = array() ) {
		$this->log( WC_Log_Levels::EMERGENCY, $message, $context );
	}

	/**
	 * Adds an alert level message.
	 *
	 * Action must be taken immediately.
	 * Example: Entire website down, database unavailable, etc.
	 *
	 * @see WC_Logger::log
	 */
	public function alert( $message, $context = array() ) {
		$this->log( WC_Log_Levels::ALERT, $message, $context );
	}

	/**
	 * Adds a critical level message.
	 *
	 * Critical conditions.
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @see WC_Logger::log
	 */
	public function critical( $message, $context = array() ) {
		$this->log( WC_Log_Levels::CRITICAL, $message, $context );
	}

	/**
	 * Adds an error level message.
	 *
	 * Runtime errors that do not require immediate action but should typically be logged
	 * and monitored.
	 *
	 * @see WC_Logger::log
	 */
	public function error( $message, $context = array() ) {
		$this->log( WC_Log_Levels::ERROR, $message, $context );
	}

	/**
	 * Adds a warning level message.
	 *
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things that are not
	 * necessarily wrong.
	 *
	 * @see WC_Logger::log
	 */
	public function warning( $message, $context = array() ) {
		$this->log( WC_Log_Levels::WARNING, $message, $context );
	}

	/**
	 * Adds a notice level message.
	 *
	 * Normal but significant events.
	 *
	 * @see WC_Logger::log
	 */
	public function notice( $message, $context = array() ) {
		$this->log( WC_Log_Levels::NOTICE, $message, $context );
	}

	/**
	 * Adds a info level message.
	 *
	 * Interesting events.
	 * Example: User logs in, SQL logs.
	 *
	 * @see WC_Logger::log
	 */
	public function info( $message, $context = array() ) {
		$this->log( WC_Log_Levels::INFO, $message, $context );
	}

	/**
	 * Adds a debug level message.
	 *
	 * Detailed debug information.
	 *
	 * @see WC_Logger::log
	 */
	public function debug( $message, $context = array() ) {
		$this->log( WC_Log_Levels::DEBUG, $message, $context );
	}

	/**
	 * Clear entries from chosen file.
	 *
	 * @deprecated 2.7.0
	 *
	 * @param string $handle
	 *
	 * @return bool
	 */
	public function clear( $handle ) {
		wc_deprecated_function( 'WC_Logger::clear', '2.7', 'WC_Log_Handler_File::clear' );
		$handler = new WC_Log_Handler_File();
		return $handler->clear( $handle );
	}
}
