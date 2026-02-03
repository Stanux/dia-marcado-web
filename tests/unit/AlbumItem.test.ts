/**
 * Unit Tests for AlbumItem Component
 * 
 * Tests the AlbumItem component to ensure it correctly displays album information
 * and handles selection states.
 * 
 * @Requirements: 3.2, 3.3
 */

import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AlbumItem from '@/Components/MediaScreen/AlbumItem.vue';
import type { Album } from '@/types/media-screen';

describe('AlbumItem.vue', () => {
  const mockAlbum: Album = {
    id: 1,
    name: 'Cerimônia',
    media_count: 5,
    media: [],
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-01T00:00:00Z',
  };

  it('deve renderizar o nome do álbum', () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: false,
      },
    });

    expect(wrapper.find('.album-name').text()).toBe('Cerimônia');
  });

  it('deve renderizar a contagem de mídias', () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: false,
      },
    });

    expect(wrapper.find('.media-count').text()).toBe('5');
  });

  it('deve exibir contagem zero quando não há mídias', () => {
    const albumWithoutMedia: Album = {
      ...mockAlbum,
      media_count: 0,
    };

    const wrapper = mount(AlbumItem, {
      props: {
        album: albumWithoutMedia,
        isSelected: false,
      },
    });

    expect(wrapper.find('.media-count').text()).toBe('0');
  });

  it('deve adicionar classe "selected" quando isSelected é true', () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: true,
      },
    });

    expect(wrapper.find('.album-item').classes()).toContain('selected');
  });

  it('não deve ter classe "selected" quando isSelected é false', () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: false,
      },
    });

    expect(wrapper.find('.album-item').classes()).not.toContain('selected');
  });

  it('deve emitir evento "click" ao clicar no item', async () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: false,
      },
    });

    await wrapper.find('.album-item').trigger('click');

    expect(wrapper.emitted('click')).toBeTruthy();
    expect(wrapper.emitted('click')).toHaveLength(1);
  });

  it('deve emitir evento "click" ao pressionar Enter', async () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: false,
      },
    });

    await wrapper.find('.album-item').trigger('keydown.enter');

    expect(wrapper.emitted('click')).toBeTruthy();
    expect(wrapper.emitted('click')).toHaveLength(1);
  });

  it('deve emitir evento "click" ao pressionar Space', async () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: false,
      },
    });

    await wrapper.find('.album-item').trigger('keydown.space');

    expect(wrapper.emitted('click')).toBeTruthy();
    expect(wrapper.emitted('click')).toHaveLength(1);
  });

  it('deve ter atributos de acessibilidade corretos', () => {
    const wrapper = mount(AlbumItem, {
      props: {
        album: mockAlbum,
        isSelected: false,
      },
    });

    const albumItem = wrapper.find('.album-item');
    expect(albumItem.attributes('role')).toBe('button');
    expect(albumItem.attributes('tabindex')).toBe('0');
  });

  it('deve renderizar corretamente com nome longo', () => {
    const albumWithLongName: Album = {
      ...mockAlbum,
      name: 'Este é um nome de álbum muito longo que deve ser truncado com ellipsis',
    };

    const wrapper = mount(AlbumItem, {
      props: {
        album: albumWithLongName,
        isSelected: false,
      },
    });

    expect(wrapper.find('.album-name').text()).toBe(albumWithLongName.name);
  });

  it('deve renderizar corretamente com contagem alta de mídias', () => {
    const albumWithManyMedia: Album = {
      ...mockAlbum,
      media_count: 999,
    };

    const wrapper = mount(AlbumItem, {
      props: {
        album: albumWithManyMedia,
        isSelected: false,
      },
    });

    expect(wrapper.find('.media-count').text()).toBe('999');
  });
});
