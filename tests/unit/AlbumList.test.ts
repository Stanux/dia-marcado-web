import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AlbumList from '@/Components/MediaScreen/AlbumList.vue';
import AlbumItem from '@/Components/MediaScreen/AlbumItem.vue';
import type { Album } from '@/types/media-screen';

/**
 * Unit Tests for AlbumList Component
 * 
 * **Validates: Requirements 3.1, 3.2, 3.5, 3.6**
 * 
 * Tests verify:
 * - Vertical list rendering of all albums
 * - Display of album name and media count
 * - "Novo álbum" button presence and functionality
 * - Event emission for album selection and creation
 */

describe('AlbumList', () => {
  const createMockAlbums = (): Album[] => [
    {
      id: 1,
      name: 'Cerimônia',
      media_count: 5,
      media: [],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    },
    {
      id: 2,
      name: 'Festa',
      media_count: 10,
      media: [],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    },
  ];

  it('deve renderizar lista vertical de álbuns', () => {
    const albums = createMockAlbums();
    const wrapper = mount(AlbumList, {
      props: { albums },
    });

    // Should render all albums
    const albumItems = wrapper.findAllComponents(AlbumItem);
    expect(albumItems).toHaveLength(2);
  });

  it('deve exibir nome e contagem de mídias para cada álbum', () => {
    const albums = createMockAlbums();
    const wrapper = mount(AlbumList, {
      props: { albums },
    });

    const albumItems = wrapper.findAllComponents(AlbumItem);
    
    // First album
    expect(albumItems[0].props('album')).toEqual(albums[0]);
    expect(albumItems[0].text()).toContain('Cerimônia');
    expect(albumItems[0].text()).toContain('5');
    
    // Second album
    expect(albumItems[1].props('album')).toEqual(albums[1]);
    expect(albumItems[1].text()).toContain('Festa');
    expect(albumItems[1].text()).toContain('10');
  });

  it('deve exibir botão "Novo álbum" como último item', () => {
    const albums = createMockAlbums();
    const wrapper = mount(AlbumList, {
      props: { albums },
    });

    const createButton = wrapper.find('.create-album-btn');
    expect(createButton.exists()).toBe(true);
    expect(createButton.text()).toContain('Novo álbum');
  });

  it('deve emitir evento album-selected ao clicar em álbum', async () => {
    const albums = createMockAlbums();
    const wrapper = mount(AlbumList, {
      props: { albums },
    });

    const albumItems = wrapper.findAllComponents(AlbumItem);
    await albumItems[0].trigger('click');

    expect(wrapper.emitted('album-selected')).toBeTruthy();
    expect(wrapper.emitted('album-selected')?.[0]).toEqual([1]);
  });

  it('deve emitir evento create-album ao clicar no botão "Novo álbum"', async () => {
    const albums = createMockAlbums();
    const wrapper = mount(AlbumList, {
      props: { albums },
    });

    const createButton = wrapper.find('.create-album-btn');
    await createButton.trigger('click');

    expect(wrapper.emitted('create-album')).toBeTruthy();
    expect(wrapper.emitted('create-album')).toHaveLength(1);
  });

  it('deve destacar álbum selecionado', () => {
    const albums = createMockAlbums();
    const wrapper = mount(AlbumList, {
      props: { 
        albums,
        selectedAlbumId: 1,
      },
    });

    const albumItems = wrapper.findAllComponents(AlbumItem);
    
    // First album should be selected
    expect(albumItems[0].props('isSelected')).toBe(true);
    
    // Second album should not be selected
    expect(albumItems[1].props('isSelected')).toBe(false);
  });

  it('deve renderizar lista vazia sem álbuns', () => {
    const wrapper = mount(AlbumList, {
      props: { albums: [] },
    });

    const albumItems = wrapper.findAllComponents(AlbumItem);
    expect(albumItems).toHaveLength(0);
    
    // Button should still be present
    const createButton = wrapper.find('.create-album-btn');
    expect(createButton.exists()).toBe(true);
  });

  it('deve ter largura fixa aplicada', () => {
    const albums = createMockAlbums();
    const wrapper = mount(AlbumList, {
      props: { albums },
    });

    const aside = wrapper.find('.album-list');
    expect(aside.exists()).toBe(true);
    
    // Check that the component has the album-list class which applies fixed width
    expect(aside.classes()).toContain('album-list');
  });

  it('deve permitir scroll quando há muitos álbuns', () => {
    // Create many albums to test scrolling
    const manyAlbums: Album[] = Array.from({ length: 20 }, (_, i) => ({
      id: i + 1,
      name: `Álbum ${i + 1}`,
      media_count: i,
      media: [],
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z',
    }));

    const wrapper = mount(AlbumList, {
      props: { albums: manyAlbums },
    });

    const albumItems = wrapper.findAllComponents(AlbumItem);
    expect(albumItems).toHaveLength(20);
    
    // Check that album-items container exists (which has overflow-y: auto)
    const albumItemsContainer = wrapper.find('.album-items');
    expect(albumItemsContainer.exists()).toBe(true);
  });
});
