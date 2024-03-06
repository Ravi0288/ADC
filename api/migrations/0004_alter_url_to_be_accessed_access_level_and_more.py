# Generated by Django 4.2 on 2024-03-04 11:24

import datetime
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('api', '0003_alter_url_to_be_accessed_last_accessed_at'),
    ]

    operations = [
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='access_level',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='contact_point_email',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='contact_point_fn',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='contact_point_type',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='description',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='distribution_title',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='distribution_type',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='identifier',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='last_accessed_at',
            field=models.DateTimeField(default=datetime.datetime(2024, 3, 4, 16, 54, 2, 83825)),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='media_type',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='modified_on',
            field=models.DateField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='program_code',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='publisher_name',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='publisher_type',
            field=models.TextField(null=True),
        ),
        migrations.AlterField(
            model_name='url_to_be_accessed',
            name='title',
            field=models.TextField(null=True),
        ),
    ]
