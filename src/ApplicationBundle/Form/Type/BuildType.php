<?php

namespace Majora\OTAStore\ApplicationBundle\Form\Type;

use Majora\OTAStore\ApplicationBundle\Entity\Build;
use Majora\OTAStore\ApplicationBundle\Form\DataTransformer\BuildUploadTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Build entity.
 */
class BuildType extends AbstractType
{
    const TOKEN_CREATION = 'creation';
    const TOKEN_EDITION = 'edition';

    /**
     * @var bool
     */
    protected $streamBuildsContent;

    /**
     * @var string
     */
    protected $webBuildsApplicationDir;

    /**
     * @var string
     */
    protected $streamBuildsApplicationDir;

    /**
     * construct.
     *
     * @param bool   $streamBuildsContent
     * @param string $webBuildsApplicationDir
     * @param string $streamBuildsApplicationDir
     */
    public function __construct($streamBuildsContent = null, $webBuildsApplicationDir = null, $streamBuildsApplicationDir = null)
    {
        $this->streamBuildsContent = $streamBuildsContent;
        $this->webBuildsApplicationDir = $webBuildsApplicationDir;
        $this->streamBuildsApplicationDir = $streamBuildsApplicationDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'majoraotastore_build';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Majora\OTAStore\ApplicationBundle\Entity\Build',
            'csrf_protection' => true,
            'allow_extra_fields' => false,
            'csrf_token_id' => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('version', TextType::class, array(
            'required' => true,
            'label' => 'build.form.version',
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder, $options) {
            $form = $event->getForm();

            /* @var \Majora\OTAStore\ApplicationBundle\Entity\Build $build */
            if (!is_null($this->streamBuildsContent)
                && $this->webBuildsApplicationDir
                && $this->streamBuildsApplicationDir
                && ($build = $event->getData())
                && ($application = $build->getApplication())
            ) {
                $formType = $builder->create('filePath', FileType::class, array(
                    'required' => $options['csrf_token_id'] === self::TOKEN_CREATION,
                    'label' => 'build.form.filePath',
                    'auto_initialize' => false,
                ));
                $formType->addModelTransformer(
                    new BuildUploadTransformer(
                        sprintf('%s/%s',
                            ($this->streamBuildsContent) ? $this->streamBuildsApplicationDir : $this->webBuildsApplicationDir,
                            $application->getSlug()
                        ),
                        $build->getFilePath()
                    )
                );

                $form->add($formType->getForm());
            }
        });
    }
}
