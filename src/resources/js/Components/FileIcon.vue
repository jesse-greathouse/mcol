<template>
  <span>
    <component v-bind:is="icon"></component>
  </span>
</template>

<script>
import { has } from '@/funcs';
import DirectoryIcon from '@/Components/FileIcons/DirectoryIcon.vue';
import DiscImageIcon from '@/Components/FileIcons/DiscImageIcon.vue';
import EbookIcon from '@/Components/FileIcons/EbookIcon.vue';
import ExeIcon from '@/Components/FileIcons/ExeIcon.vue';
import FlacIcon from '@/Components/FileIcons/FlacIcon.vue';
import GenericIcon from '@/Components/FileIcons/GenericIcon.vue';
import LinkedLibraryIcon from '@/Components/FileIcons/LinkedLibraryIcon.vue';
import MkvIcon from '@/Components/FileIcons/MkvIcon.vue';
import MusicIcon from '@/Components/FileIcons/MusicIcon.vue';
import MobileIcon from '@/Components/FileIcons/MobileIcon.vue';
import Mp4Icon from '@/Components/FileIcons/Mp4Icon.vue';
import NspIcon from '@/Components/FileIcons/NspIcon.vue';
import PackageIcon from '@/Components/FileIcons/PackageIcon.vue';
import PdfIcon from '@/Components/FileIcons/PdfIcon.vue';
import RarIcon from '@/Components/FileIcons/RarIcon.vue';
import SrtIcon from '@/Components/FileIcons/SrtIcon.vue';
import TarIcon from '@/Components/FileIcons/TarIcon.vue';
import TxtIcon from '@/Components/FileIcons/TxtIcon.vue';
import VideoIcon from '@/Components/FileIcons/VideoIcon.vue';
import ZipIcon from '@/Components/FileIcons/ZipIcon.vue';

const extensionToComponentMap = {
  avi: 'VideoIcon',
  dll: 'LinkedLibraryIcon',
  dmg: 'DiscImageIcon',
  epub: 'EbookIcon',
  exe: 'ExeIcon',
  flac: 'FlacIcon',
  iso: 'DiscImageIcon',
  m4a: 'MusicIcon',
  m4b: 'EbookIcon',
  m4v: 'VideoIcon',
  mkv: 'MkvIcon',
  mobi: 'MobileIcon',
  mp3: 'MusicIcon',
  mp4: 'Mp4Icon',
  nsp: 'NspIcon',
  pdf: 'PdfIcon',
  pkg: 'PackageIcon',
  rar: 'RarIcon',
  srt: 'SrtIcon',
  tar: 'TarIcon',
  txt: 'TxtIcon',
  wav: 'MusicIcon',
  zip: 'ZipIcon',
};

export default {
  components: {
    DirectoryIcon,
    DiscImageIcon,
    EbookIcon,
    ExeIcon,
    FlacIcon,
    GenericIcon,
    LinkedLibraryIcon,
    MkvIcon,
    MusicIcon,
    MobileIcon,
    Mp4Icon,
    NspIcon,
    PackageIcon,
    PdfIcon,
    RarIcon,
    SrtIcon,
    TarIcon,
    TxtIcon,
    VideoIcon,
    ZipIcon,
  },
  props: {
    extension: String,
    isDir: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    let component = 'GenericIcon';
    if (this.isDir) {
      component = 'DirectoryIcon';
    }

    if (has(extensionToComponentMap, this.extension)) {
      component = extensionToComponentMap[this.extension];
    }

    return {
      icon: component,
    };
  },
};
</script>
