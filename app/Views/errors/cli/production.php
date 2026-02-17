<?php

use CodeIgniter\CLI\CLI;

CLI::error('ERROR: 500');
CLI::write(lang('App.error500Title'));
CLI::write(lang('App.error500Body'));
CLI::newLine();
