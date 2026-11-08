import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import SearchSelect, { type SearchOption } from '../SearchSelect.vue'

const people: SearchOption[] = [
  { id: 1, label: 'Émilie Lefèvre' },
  { id: 2, label: 'Garage Maes BV' },
]

function mountSelect(
  search: (term: string) => Promise<SearchOption[]>,
  modelValue: number | '' = '',
) {
  return mount(SearchSelect, {
    props: {
      id: 'client_id',
      modelValue,
      search,
      resolve: async (id: number) => people.find((person) => person.id === id) ?? null,
      'onUpdate:modelValue': vi.fn(),
    },
    attachTo: document.body,
  })
}

function lastEmitted(wrapper: ReturnType<typeof mountSelect>): unknown {
  const events = wrapper.emitted('update:modelValue') ?? []

  return events[events.length - 1]
}

describe('SearchSelect', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => {
    vi.useRealTimers()
    document.body.innerHTML = ''
  })

  it('searches once typing pauses, then chooses with the keyboard', async () => {
    const search = vi.fn().mockResolvedValue(people)
    const wrapper = mountSelect(search)
    const input = wrapper.get('input')

    await input.setValue('lef')
    await input.setValue('lefe')
    expect(search).not.toHaveBeenCalledWith('lefe')

    await vi.advanceTimersByTimeAsync(250)
    await flushPromises()
    expect(search).toHaveBeenCalledTimes(1)
    expect(search).toHaveBeenCalledWith('lefe')
    expect(input.attributes('aria-expanded')).toBe('true')

    await input.trigger('keydown', { key: 'ArrowDown' })
    await input.trigger('keydown', { key: 'Enter' })

    expect(lastEmitted(wrapper)).toEqual([2])
    expect((input.element as HTMLInputElement).value).toBe('Garage Maes BV')
    expect(input.attributes('aria-expanded')).toBe('false')
  })

  it('shows the name of a value chosen elsewhere, such as from a link', async () => {
    const wrapper = mountSelect(vi.fn().mockResolvedValue([]), 1)
    await flushPromises()

    expect((wrapper.get('input').element as HTMLInputElement).value).toBe('Émilie Lefèvre')
  })

  it('clears the choice once the text no longer matches it', async () => {
    const wrapper = mountSelect(vi.fn().mockResolvedValue(people), 1)
    await flushPromises()

    await wrapper.get('input').setValue('Émilie')

    expect(lastEmitted(wrapper)).toEqual([''])
  })

  it('ignores an older answer that arrives after a newer one', async () => {
    let answerFirst: (options: SearchOption[]) => void = () => {}
    const search = vi
      .fn()
      .mockImplementationOnce(
        () => new Promise<SearchOption[]>((resolve) => (answerFirst = resolve)),
      )
      .mockResolvedValueOnce([people[1]!])
    const wrapper = mountSelect(search)
    const input = wrapper.get('input')

    await input.setValue('a')
    await vi.advanceTimersByTimeAsync(250)
    await input.setValue('garage')
    await vi.advanceTimersByTimeAsync(250)
    await flushPromises()

    answerFirst(people)
    await flushPromises()

    expect(wrapper.findAll('[role="option"]').map((option) => option.text())).toEqual([
      'Garage Maes BV',
    ])
  })
})
