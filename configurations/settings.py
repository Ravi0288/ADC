"""
Django settings for nal_library project
"""

from pathlib import Path
import os
from .conf import get_env_variable

# Build paths inside the project like this: BASE_DIR / 'subdir'.
BASE_DIR = Path(__file__).resolve().parent.parent


# SECURITY WARNING: keep the secret key used in production secret!
SECRET_KEY = 'django-insecure-a(f9x&9kn8iwn&thlk_3j_48eu5rn0x*4h@xi+@6^%p-)=7-7k'

# SECURITY WARNING: don't run with debug turned on in production!
DEBUG = True


# List of whitelisted host to be proivded here
ALLOWED_HOSTS = ['*']


# Application definition
# ..................######
INSTALLED_APPS = [
    # 'admin_black.apps.AdminBlackConfig',
    'django.contrib.admin',
    'django.contrib.auth',
    'django.contrib.contenttypes',
    'django.contrib.sessions',
    'django.contrib.messages',
    'django.contrib.staticfiles',
    'django_browser_reload',
    'rest_framework',
    'api',
]
# ..................#######

# Middlewares to be used in this project
# ..................#############
MIDDLEWARE = [
    'django.middleware.security.SecurityMiddleware',
    "whitenoise.middleware.WhiteNoiseMiddleware",
    'django.contrib.sessions.middleware.SessionMiddleware',
    'django.middleware.common.CommonMiddleware',
    'django.middleware.csrf.CsrfViewMiddleware',
    'django.contrib.auth.middleware.AuthenticationMiddleware',
    'django.contrib.messages.middleware.MessageMiddleware',
    'django.middleware.clickjacking.XFrameOptionsMiddleware',
    'django_browser_reload.middleware.BrowserReloadMiddleware',
]
# ..................#############


# Root URL file path
ROOT_URLCONF = 'configurations.urls'
HOME_TEMPLATES = os.path.join(BASE_DIR, 'templates')


# Template for serving the result
# ..................###############
TEMPLATES = [
    {
        'BACKEND': 'django.template.backends.django.DjangoTemplates',
        'DIRS': [BASE_DIR, HOME_TEMPLATES],
        'APP_DIRS': True,
        'OPTIONS': {
            'context_processors': [
                'django.template.context_processors.debug',
                'django.template.context_processors.request',
                'django.contrib.auth.context_processors.auth',
                'django.contrib.messages.context_processors.messages',
            ],
        },
    },
]
# ..................#####################


WSGI_APPLICATION = 'configurations.wsgi.application'



# Database settings
# ..................#####################
# DATABASES = {
#     'default': {
#     'ENGINE': 'django.db.backends.mysql',
#     'NAME': 'adc',
    # 'USER':get_env_variable('DBUSER'),
    # 'PASSWORD':get_env_variable('DBPSWD'),
#     'HOST':'localhost',
#     'PORT':'3306',
#     }
# }

DATABASE_DIR = os.path.join(BASE_DIR, 'db.sqlite3')
DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.sqlite3',
        'NAME': DATABASE_DIR,
    }
}



# ..................#####################




# Default Django password validations
# ..................#######################
AUTH_PASSWORD_VALIDATORS = [
    {
        'NAME': 'django.contrib.auth.password_validation.UserAttributeSimilarityValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.MinimumLengthValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.CommonPasswordValidator',
    },
    {
        'NAME': 'django.contrib.auth.password_validation.NumericPasswordValidator',
    },
]
# ..................##################





# Rest framework authentication
# This settings is for preventing the endpoints from unauthorised access.
# ..................################
# REST_FRAMEWORK = {
#     'DEFAULT_AUTHENTICATION_CLASSES': [
#         'rest_framework.authentication.TokenAuthentication',
#     ],
#     'DEFAULT_PERMISSION_CLASSES':(
#         'rest_framework.permissions.IsAuthenticated',
#     ),
# }
# ..................##################




# Internationalization
# .........#####################
LANGUAGE_CODE = 'en-us'

TIME_ZONE = 'UTC'

USE_I18N = True

USE_TZ = True

# .........#####################



# Static files (CSS, JavaScript, Images)
STATIC_URL = '/static/'
STATIC_ROOT = BASE_DIR / 'staticfiles'

STATICFILES_DIRS = [
    os.path.join(BASE_DIR, "static"),
    ]

STATICFILES_FINDERS = (
    'django.contrib.staticfiles.finders.FileSystemFinder',
    'django.contrib.staticfiles.finders.AppDirectoriesFinder',
    # django.contrib.staticfiles.finders.DefaultStorageFinder',
)

MEDIA_URL = '/media/'
MEDIA_ROOT = BASE_DIR / 'media_library'
STAKEHOLDERS_ROOT = BASE_DIR / 'DOCUMENTS'
ARTICLES = BASE_DIR / 'ARTICLES'
LOGS = BASE_DIR / 'logs'

ERS_LOGS_STAGE = os.path.join(BASE_DIR, 'api/datagov/ERS/log/stage')
ERS_LOGS_PROD = os.path.join(BASE_DIR, 'api/datagov/ERS/log/prod')

FAS_LOGS_STAGE = os.path.join(BASE_DIR, 'api/datagov/FAS/log/stage')
FAS_LOGS_PROD = os.path.join(BASE_DIR, 'api/datagov/FAS/log/prod')

FSA_LOGS_STAGE = os.path.join(BASE_DIR, 'api/datagov/FSA/log/stage')
FSA_LOGS_PROD = os.path.join(BASE_DIR, 'api/datagov/FSA/log/prod')

FSLM_LOGS_STAGE = os.path.join(BASE_DIR, 'api/datagov/FSLM/log/stage')
FSLM_LOGS_PROD = os.path.join(BASE_DIR, 'api/datagov/FSLM/log/prod')

NRCS_LOGS_STAGE = os.path.join(BASE_DIR, 'api/datagov/NRCS/log/stage')
NRCS_LOGS_PROD = os.path.join(BASE_DIR, 'api/datagov/NRCS/log/prod')

LOGIN_REDIRECT_URL = '/'

# Default primary key field type
DEFAULT_AUTO_FIELD = 'django.db.models.BigAutoField'


# JSON_SOURCE = 'https://www.usda.gov/sites/default/files/documents/data.json'
TOKEN = 'Bearer fe5640f200496e3f789b395666665d817925eb13f40b00a239657d4a83f576fc01aa36bdacad9d12a27cd475edbeb8afd4604363611dd41ff0301ad7a2b8ee88'

DATA_GOV_MAPPING={
    "005:13":"ERS",
    "005:68":"FAS",
    "005:49":"FSA",
    "005:53":"NRCS",
    "005:96":"FSLM"
}
# logger to log errors in file
LOGGING = {
    'version': 1,
    'disable_existing_loggers': False,
    'formatters': {
        'verbose': {
            'format': '{levelname} {asctime} {module} {process:d} {thread:d} {message}',
            'style': '{',
        },
        'simple': {
            'format': '{levelname} {message}',
            'style': '{',
        },
    },
    'handlers': {
        'logfile': {
            'level': 'DEBUG',
            'class': 'logging.handlers.RotatingFileHandler',
            'filename': "ADC.log",
            'maxBytes': 100000,
            'backupCount': 2,
            'formatter': 'verbose',
        },
    },
    'loggers': {
        'django': {
            'handlers': ['logfile'],
            # DEBUG: Low-level system information
            # INFO: General system information
            # WARNING: Minor problems related information
            # ERROR: Major problems related information
            # CRITICAL: Critical problems related information
            # here we will log only error and critical (greater than error level)
            'level' : 'ERROR',
            'propagate': True,
        },
        'apps': {
            'handlers': ['logfile'],
            'level': 'ERROR',
            'propagate': True,
        },
    },
}
